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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class Note extends Model
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string
     */
    protected $table = 'notes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'content',
        'title',
        'pinned',
    ];

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'pinned' => 'boolean',
    ];

    /**
     * Toggle the note title and content values.
     *
     * Ensure every note can be decrypted before updating them in the database.
     *
     * @param User $user
     * @param bool $previouslyEncrypted
     * @param bool $currentlyEncrypted
     */
    public static function toggleEncryption(User $user, bool $previouslyEncrypted, bool $currentlyEncrypted)
    {
        $notes = DB::table('notes')->where('user_id', $user->id)->get();

        foreach ($notes as &$note) {
            if ($previouslyEncrypted && !$currentlyEncrypted) {
                $note->title = Crypt::decrypt($note->title);
                $note->content = Crypt::decrypt($note->content);
            } else if (!$previouslyEncrypted && $currentlyEncrypted) {
                $note->title = Crypt::encrypt($note->title);
                $note->content = Crypt::encrypt($note->content);
            } else {
                continue;
            }
        }

        foreach ($notes as $note) {
            DB::table('notes')->where('id', $note->id)->update([
                'title' => $note->title,
                'content' => $note->content
            ]);
        }
    }

    /**
     * Get the note's title.
     *
     * @param string $value
     * @return string
     */
    public function getTitleAttribute($value)
    {
        return Auth::user()->encrypt ? Crypt::decrypt($value) : $value;
    }

    /**
     * Set the note's title.
     *
     * @param string $value
     * @return void
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = Auth::user()->encrypt ? Crypt::encrypt($value) : $value;
    }

    /**
     * Get the note's content.
     *
     * @param string $value
     * @return string
     */
    public function getContentAttribute($value)
    {
        return Auth::user()->encrypt ? Crypt::decrypt($value) : $value;
    }

    /**
     * Set the note's content.
     *
     * @param string $value
     * @return void
     */
    public function setContentAttribute($value)
    {
        $this->attributes['content'] = Auth::user()->encrypt ? Crypt::encrypt($value) : $value;
    }
}
