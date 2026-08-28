/*
  Plainpad - Self Hosted Note Taking App

  Copyright (C) Alex Tselegidis - https://alextselegidis.com

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
import {Input, InputGroup, InputGroupAddon, InputGroupText, ListGroup, ListGroupItem, Modal, ModalBody} from 'reactstrap';
import {translate} from '../../lang';
import storage from '../../storage';
import notes from '../../stores/notes';
import account from '../../stores/account';

const LIMIT = 10;

class QuickSearch extends Component {
  state = {keyword: '', notes: [], active: 0};
  input = React.createRef();

  async onOpened() {
    if (this.input.current) {
      this.input.current.focus();
    }

    const all = [];
    await storage.table('notes').iterate((note) => {
      all.push(note);
    });
    this.setState({notes: all});
  }

  matches() {
    const {keyword, notes} = this.state;
    const needle = keyword.trim().toLowerCase();

    return notes
      .filter((note) => !needle || note.title.toLowerCase().includes(needle))
      .sort((a, b) => {
        if (!a.pinned !== !b.pinned) {
          return a.pinned ? 1 : -1; // pinned notes go last
        }

        return a.updated_at < b.updated_at ? 1 : -1;
      })
      .slice(0, LIMIT);
  }

  select(note) {
    if (!note) {
      return;
    }

    this.props.toggle();
    notes.select(note.id);
  }

  // Offer a way out when the keyword matches nothing, without ever creating a note on its own.

  creatable() {
    return !!this.state.keyword.trim() && !this.matches().length;
  }

  // The trailing null is the create entry, so it navigates like any other result.

  options() {
    const results = this.matches();

    return this.creatable() ? [...results, null] : results;
  }

  create() {
    this.props.toggle();
    notes.add(this.state.keyword.trim());
  }

  onKeyDown(event) {
    const options = this.options();
    const {active} = this.state;

    if (event.key === 'ArrowDown') {
      event.preventDefault();
      this.setState({active: Math.min(active + 1, options.length - 1)});
    } else if (event.key === 'ArrowUp') {
      event.preventDefault();
      this.setState({active: Math.max(active - 1, 0)});
    } else if (event.key === 'Enter') {
      event.preventDefault();

      if (options[active]) {
        this.select(options[active]);
      } else if (this.creatable()) {
        this.create();
      }
    }
  }

  render() {
    const {isOpen, toggle} = this.props;
    const {keyword, active} = this.state;
    const results = this.matches();
    const creatable = this.creatable();

    return (
      <Modal isOpen={isOpen} toggle={toggle} className={`quick-search ${account.user && account.user.theme === 'dark' ? 'dark' : ''}`}
             onOpened={() => this.onOpened()}
             onClosed={() => this.setState({keyword: '', active: 0})}>
        <ModalBody className="p-0">
          <InputGroup>
            <InputGroupAddon addonType="prepend">
              <InputGroupText className="border-0 rounded-0 bg-transparent pl-4 pr-0">
                <i className="fa fa-search" />
              </InputGroupText>
            </InputGroupAddon>

            <Input innerRef={this.input} autoFocus bsSize="lg" value={keyword} className="border-0 shadow-none rounded-0 px-3 py-4"
                   placeholder={translate('notes.search')}
                   onChange={(event) => this.setState({keyword: event.target.value, active: 0})}
                   onKeyDown={(event) => this.onKeyDown(event)} />
          </InputGroup>

          {results.length || creatable ? (
            <ListGroup flush>
              {results.map((note, index) => (
                <ListGroupItem key={note.id} action active={index === active} tag="button"
                               onMouseEnter={() => this.setState({active: index})}
                               onClick={() => this.select(note)}>
                  {note.pinned ? <i className="fa fa-thumb-tack mr-2" /> : null}
                  {note.title}
                </ListGroupItem>
              ))}

              {creatable ? (
                <ListGroupItem action active={active === results.length} tag="button"
                               onMouseEnter={() => this.setState({active: results.length})}
                               onClick={() => this.create()}>
                  <i className="fa fa-plus mr-2" />
                  {translate('notes.createNote')}: <strong>{keyword.trim()}</strong>
                </ListGroupItem>
              ) : null}
            </ListGroup>
          ) : null}
        </ModalBody>
      </Modal>
    );
  }
}

export default QuickSearch;
