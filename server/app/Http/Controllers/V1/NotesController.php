<?php

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
        $request->validate( [
            'title' => 'required|string',
            'content' => 'present|nullable|string', // An emptied note is still a valid note.
            'pinned' => 'required|boolean'
        ]);

        $note = new Note;
        $note->id = (string)Str::uuid();
        $note->user_id = Auth::user()->id;
        $note->title = $request->input('title');
        $note->content = $request->input('content') ?? '';
        $note->pinned = $request->input('pinned');
        $note->save();

        return response()->json($note, 201);
    }

    public function update(Request $request, string $id)
    {
        $request->validate( [
            'title' => 'required|string',
            'content' => 'present|nullable|string', // An emptied note is still a valid note.
            'pinned' => 'required|boolean'
        ]);

        $note = Note::find($id);

        if (!$note) {
            return response('', 404);
        }

        if ($note->user_id !== Auth::user()->id) {
            return response('', 404); // Not 401, the client treats that as an expired session and logs out.
        }

        $note->title = $request->input('title');
        $note->content = $request->input('content') ?? '';
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
            return response('', 404); // Not 401, the client treats that as an expired session and logs out.
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

        $request->validate([
            'filter' => 'nullable|string',
            'sort' => 'nullable|string',
            'direction' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'length' => 'nullable|integer|min:1',
            'fields' => 'nullable|string',
        ]);

        $filter = $request->input('filter');
        $sort = in_array($request->input('sort'), $defaultFields, true) ? $request->input('sort') : 'title';
        $direction = strtolower($request->input('direction')) === 'desc' ? 'desc' : 'asc';
        $page = $request->input('page');
        $length = $request->input('length');
        $fields = $request->input('fields') ? array_intersect($defaultFields, explode(',', $request->input('fields'))) : $defaultFields;

        $user = Auth::user();

        $query = Note::where('user_id', $user->id)
            ->select(...$fields)
            ->orderBy($sort, $direction);

        // Filtered in PHP because encrypted notes cannot be searched in SQL. Without a filter (every client sync) the
        // contents are not loaded at all.
        if ($filter) {
            $occurrences = Note::where('user_id', $user->id)
                ->select('id', 'title', 'content')
                ->get()
                ->filter(function($note) use ($filter) {
                    return stripos($note->title, $filter) !== false || stripos($note->content, $filter) !== false;
                })
                ->map(function($note) {
                    return $note->id;
                });

            $query->whereIn('id', $occurrences);
        }

        if ($length) {
            $query->limit($length);
            $query->offset((($page ?? 1) - 1) * $length);
        }

        $notes = $query->get();

        return response()->json($notes);
    }

    public function retrieve(Request $request, string $id)
    {
        $note = Note::find($id);

        if (!$note) {
            return response('', 404);
        }

        if ($note->user_id !== Auth::user()->id) {
            return response('', 404); // Not 401, the client treats that as an expired session and logs out.
        }

        return response()->json($note);
    }

    public function pinned(Request $request, string $id)
    {
        $request->validate( [
            'pinned' => 'required|boolean'
        ]);

        $note = Note::find($id);

        if (!$note) {
            return response('', 404);
        }

        if ($note->user_id !== Auth::user()->id) {
            return response('', 404); // Not 401, the client treats that as an expired session and logs out.
        }

        $note->pinned = $request->input('pinned');
        $note->save();

        return response('', 200);
    }
}
