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

class NoteContextMenu extends Component {
  constructor(props) {
    super(props);
    this.handleCopy = this.handleCopy.bind(this);
    this.handleCut = this.handleCut.bind(this);
    this.handlePaste = this.handlePaste.bind(this);
    this.handleShowOriginalMenu = this.handleShowOriginalMenu.bind(this);
  }

  handleCopy() {
    const { textareaRef, onClose } = this.props;
    const ta = textareaRef.current;
    if (!ta) return;

    const start = ta.selectionStart;
    const end = ta.selectionEnd;
    const selectedText = ta.value.substring(start, end);

    if (selectedText) {
      navigator.clipboard.writeText(selectedText);
    }
    onClose();
  }

  handleCut() {
    const { textareaRef, onContentUpdate, onClose } = this.props;
    const ta = textareaRef.current;
    if (!ta) return;

    const start = ta.selectionStart;
    const end = ta.selectionEnd;
    const selectedText = ta.value.substring(start, end);

    if (selectedText) {
      navigator.clipboard.writeText(selectedText);
      const newValue = ta.value.substring(0, start) + ta.value.substring(end);
      onContentUpdate(newValue);

      setTimeout(() => {
        try {
          if (ta.setSelectionRange) {
            ta.setSelectionRange(start, start);
          }
        } catch (e) {
          // ignore if unable to set selection
        }
      }, 0);
    }
    onClose();
  }

  async handlePaste() {
    const { textareaRef, onContentUpdate, onClose } = this.props;
    const ta = textareaRef.current;
    if (!ta) return;

    try {
      const clipboardText = await navigator.clipboard.readText();
      const start = ta.selectionStart;
      const end = ta.selectionEnd;
      const value = ta.value || '';

      const newValue = value.substring(0, start) + clipboardText + value.substring(end);
      onContentUpdate(newValue);

      setTimeout(() => {
        try {
          if (ta.setSelectionRange) {
            const pos = start + clipboardText.length;
            ta.setSelectionRange(pos, pos);
          }
        } catch (e) {
          // ignore if unable to set selection
        }
      }, 0);
    } catch (e) {
      console.error('Failed to read clipboard:', e);
    }
    onClose();
  }

  handleShowOriginalMenu() {
    const { textareaRef, onClose, onShowNativeMenu } = this.props;
    onClose();
    onShowNativeMenu();
  }

  render() {
    const { visible, x, y, pinned, notes, onClose } = this.props;

    if (!visible) {
      return null;
    }

    return (
      <div
        className="custom-context-menu"
        style={{
          position: 'fixed',
          top: `${y}px`,
          left: `${x}px`,
          backgroundColor: 'white',
          border: '1px solid #ccc',
          borderRadius: '4px',
          boxShadow: '0 2px 10px rgba(0,0,0,0.2)',
          zIndex: 10000,
          minWidth: '180px'
        }}
      >
        <div
          className="context-menu-item"
          onClick={this.handleCopy}
          style={{
            padding: '8px 16px',
            cursor: 'pointer',
            borderBottom: '1px solid #eee'
          }}
          onMouseEnter={(e) => e.currentTarget.style.backgroundColor = '#f5f5f5'}
          onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'white'}
        >
          <i className="fa fa-copy mr-2"></i>
          Copy
        </div>
        <div
          className="context-menu-item"
          onClick={this.handleCut}
          style={{
            padding: '8px 16px',
            cursor: 'pointer',
            borderBottom: '1px solid #eee'
          }}
          onMouseEnter={(e) => e.currentTarget.style.backgroundColor = '#f5f5f5'}
          onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'white'}
        >
          <i className="fa fa-cut mr-2"></i>
          Cut
        </div>
        <div
          className="context-menu-item"
          onClick={this.handlePaste}
          style={{
            padding: '8px 16px',
            cursor: 'pointer',
            borderBottom: '1px solid #eee'
          }}
          onMouseEnter={(e) => e.currentTarget.style.backgroundColor = '#f5f5f5'}
          onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'white'}
        >
          <i className="fa fa-paste mr-2"></i>
          Paste
        </div>
        <div
          className="context-menu-item"
          onClick={() => {
            notes.download();
            onClose();
          }}
          style={{
            padding: '8px 16px',
            cursor: 'pointer',
            borderBottom: '1px solid #eee'
          }}
          onMouseEnter={(e) => e.currentTarget.style.backgroundColor = '#f5f5f5'}
          onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'white'}
        >
          <i className="fa fa-cloud-download mr-2"></i>
          Download
        </div>
        {navigator.canShare && (
          <div
            className="context-menu-item"
            onClick={() => {
              notes.share();
              onClose();
            }}
            style={{
              padding: '8px 16px',
              cursor: 'pointer',
              borderBottom: '1px solid #eee'
            }}
            onMouseEnter={(e) => e.currentTarget.style.backgroundColor = '#f5f5f5'}
            onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'white'}
          >
            <i className="fa fa-share-alt mr-2"></i>
            Share
          </div>
        )}
        <div
          className="context-menu-item"
          onClick={() => {
            if (pinned) {
              notes.unpin();
            } else {
              notes.pin();
            }
            onClose();
          }}
          style={{
            padding: '8px 16px',
            cursor: 'pointer',
            borderBottom: '1px solid #eee'
          }}
          onMouseEnter={(e) => e.currentTarget.style.backgroundColor = '#f5f5f5'}
          onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'white'}
        >
          <i className="fa fa-thumb-tack mr-2"></i>
          {pinned ? 'Unpin' : 'Pin'}
        </div>
        <div
          className="context-menu-item"
          onClick={() => {
            notes.delete();
            onClose();
          }}
          style={{
            padding: '8px 16px',
            cursor: 'pointer',
            borderBottom: '1px solid #eee',
            color: '#d94e5c'
          }}
          onMouseEnter={(e) => e.currentTarget.style.backgroundColor = '#f5f5f5'}
          onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'white'}
        >
          <i className="fa fa-trash mr-2"></i>
          Delete
        </div>
        <div
          className="context-menu-item"
          onClick={this.handleShowOriginalMenu}
          style={{
            padding: '8px 16px',
            cursor: 'pointer'
          }}
          onMouseEnter={(e) => e.currentTarget.style.backgroundColor = '#f5f5f5'}
          onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'white'}
        >
          <i className="fa fa-ellipsis-h mr-2"></i>
          Show Original Menu
        </div>
      </div>
    );
  }
}

export default NoteContextMenu;

