<?php

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

namespace App\Http\Controllers\V1;


use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class NotesController extends Controller
{
    public function create(Request $request)
    {
        $this->validate($request, [
            'title' => 'required|string',
            'content' => 'required|string',
            'pinned' => 'required|boolean'
        ]);

        $note = new Note;
        $note->id = (string)Str::uuid();
        $note->user_id = Auth::user()->id;
        $note->title = $request->input('title');
        $note->content = $request->input('content');
        $note->pinned = $request->input('pinned');
        $note->save();

        return response()->json($note, 201);
    }

    public function update(Request $request, string $id)
    {
        $this->validate($request, [
            'title' => 'required|string',
            'content' => 'required|string',
            'pinned' => 'required|boolean'
        ]);

        $note = Note::find($id);

        if (!$note) {
            return response('', 404);
        }

        if ($note->user_id !== Auth::user()->id) {
            return response('', 401);
        }

        $note->title = $request->input('title');
        $note->content = $request->input('content');
        $note->pinned = $request->input('pinned');
        $note->save();

        return response()->json($note, 200);
    }

    public function delete(Request $request, string $id)
    {
        $note = Note::find($id);

        if (!$note) {
            return response('', 404);
        }

        if ($note->user_id !== Auth::user()->id) {
            return response('', 401);
        }

        $note->delete();

        return response('', 200);
    }

    public function list(Request $request)
    {
        $defaultFields = [
            'id',
            'user_id',
            'content',
            'title',
            'pinned',
            'created_at',
            'updated_at'
        ];

        $filter = $request->input('filter');
        $sort = $request->input('sort') ?? 'title';
        $direction = $request->input('direction') ?? 'asc';
        $page = $request->input('page') ?? 1;
        $length = $request->input('length') ?? 1000;
        $fields = $request->input('fields') ? array_intersect($defaultFields, explode(',', $request->input('fields'))) : $defaultFields;

        $user = Auth::user();

        $occurrences = Note::where('user_id', $user->id)
            ->select('id', 'title', 'content')
            ->get()
            ->filter(function($note) use ($filter) {
                if (!$filter) {
                    return true;
                }

                return stripos($note->title, $filter) !== false || stripos($note->content, $filter);
            })
            ->map(function($note) {
                return $note->id;
            });

        $notes = Note::whereIn('id', $occurrences)
            ->where('user_id', $user->id)
            ->select(...$fields)
            ->orderBy($sort, $direction)
            ->limit($length)
            ->offset(($page - 1) * $length)
            ->get();

        return response()->json($notes);
    }

    public function retrieve(Request $request, string $id)
    {
        $note = Note::find($id);

        if (!$note) {
            return response('', 404);
        }

        if ($note->user_id !== Auth::user()->id) {
            return response('', 401);
        }

        return response()->json($note);
    }

    public function pinned(Request $request, string $id)
    {
        $this->validate($request, [
            'pinned' => 'required|boolean'
        ]);

        $note = Note::find($id);

        if (!$note) {
            return response('', 404);
        }

        if ($note->user_id !== Auth::user()->id) {
            return response('', 401);
        }

        $note->pinned = $request->input('pinned');
        $note->save();

        return response('', 200);
    }
}
