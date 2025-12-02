/*
  Plainpad - Self Hosted Note Taking App

  Copyright (C) 2020 Alex Tselegidis - https://alextselegidis.com

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <https://www.gnu.org/licenses/>
*/

import React, {Component} from 'react';
import {inject, observer} from 'mobx-react';
import TextareaAutosize from 'react-textarea-autosize';
import WelcomeMessage from './WelcomeMessage';
import NoteContextMenu from './NoteContextMenu';

class Notes extends Component {
  scrollY = null;

  constructor(props) {
    super(props);
    this.textarea = React.createRef();
    this.state = {
      contextMenu: {
        visible: false,
        x: 0,
        y: 0
      },
      disableCustomMenu: false
    };
    this.handleKeyDown = this.handleKeyDown.bind(this);
    this.handleContextMenu = this.handleContextMenu.bind(this);
    this.hideContextMenu = this.hideContextMenu.bind(this);
    this.handleShowNativeMenu = this.handleShowNativeMenu.bind(this);
  }

  handleShowNativeMenu() {
    // Disable custom menu and allow native menu on next right-click
    this.setState({ disableCustomMenu: true }, () => {
      // Focus the textarea
      if (this.textarea.current) {
        this.textarea.current.focus();
      }
    });
  }

  handleKeyDown(event) {
    // preserve previous behavior
    this.scrollY = window.scrollY;

    // Handle Tab key: insert 4 spaces at cursor position or replace selection
    if (event.key === 'Tab' || event.keyCode === 9) {
      event.preventDefault();

      const ta = this.textarea.current;
      const { notes } = this.props;

      if (!ta) {
        return;
      }

      const start = ta.selectionStart;
      const end = ta.selectionEnd;
      const value = ta.value || '';
      const insert = '    ';

      const newValue = value.substring(0, start) + insert + value.substring(end);

      // Update the store (which will update the textarea value)
      notes.updateContent(newValue);

      // Restore caret position after DOM update
      setTimeout(() => {
        try {
          if (ta.setSelectionRange) {
            const pos = start + insert.length;
            ta.setSelectionRange(pos, pos);
          }
        } catch (e) {
          // ignore if unable to set selection
        }
      }, 0);
    }
  }

  handleContextMenu(event) {
    // Allow native context menu if disabled flag is set
    if (this.state.disableCustomMenu) {
      // Re-enable custom menu after this event
      setTimeout(() => {
        this.setState({ disableCustomMenu: false });
      }, 100);
      return;
    }

    event.preventDefault();

    this.setState({
      contextMenu: {
        visible: true,
        x: event.clientX,
        y: event.clientY
      }
    });
  }

  hideContextMenu() {
    this.setState({
      contextMenu: {
        visible: false,
        x: 0,
        y: 0
      }
    });
  }


  componentDidMount() {
    const {
      account,
      notes,
      match
    } = this.props;

    if (!account.session) {
      return;
    }

    const id = match.params.id;

    if (id) {
      notes.select(id);
    }

    // Add click listener to hide context menu when clicking outside
    document.addEventListener('click', this.hideContextMenu);
  }

  componentDidUpdate(prevProps, prevState, snapshot) {
    const {
      notes,
      match
    } = this.props;

    const id = match.params.id;
    const previousId = prevProps.match.params.id;

    if (id && id !== previousId) {
      notes.select(id);
    }
  }

  componentWillUnmount() {
    // Reset notes state
    this.props.notes.reset();

    // Ensure we're in a browser environment before touching document
    if (typeof document !== 'undefined' && document && document.title !== undefined) {
      document.title = 'Plainpad';
    }

    // Clean up event listener
    document.removeEventListener('click', this.hideContextMenu);
  }

  render() {
    const {
      account,
      notes
    } = this.props;

    const {
      id,
      content,
      pinned
    } = notes;

    if (!id && !content) {
      return <WelcomeMessage/>;
    }

    const {
      user
    } = account;

    const { contextMenu } = this.state;

    return (
      <div className="animated fadeIn h-100 py-2">
        <TextareaAutosize
          ref={this.textarea}
          className={`note-content ${user.line === 'full' ? 'full-line' : 'narrow-line'}`}
          value={content}
          onKeyDown={this.handleKeyDown}
          onContextMenu={this.handleContextMenu}
          onChange={(event) => notes.updateContent(event.target.value)}
          onHeightChange={(height) => notes.applyScrollFix(this.textarea.current, this.scrollY, height)}
        />

        <NoteContextMenu
          visible={contextMenu.visible}
          x={contextMenu.x}
          y={contextMenu.y}
          textareaRef={this.textarea}
          notes={notes}
          pinned={pinned}
          onClose={this.hideContextMenu}
          onContentUpdate={(newValue) => notes.updateContent(newValue)}
          onShowNativeMenu={this.handleShowNativeMenu}
        />
      </div>
    );
  }
}

export default inject('account', 'notes')(
  observer(Notes)
);
