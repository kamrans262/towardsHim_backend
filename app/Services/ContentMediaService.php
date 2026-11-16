<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContentMediaService
{
    /** Files created during a transaction, cleaned up if the save fails. */
    private array $newFiles = [];
    /** Files superseded by a successful save, deleted only after commit. */
    private array $oldFiles = [];

    private const FILE_TYPES = [
        'audio' => ['mp3', 'm4a', 'aac', 'ogg', 'wav'],
        'video' => ['mp4', 'mov', 'webm', 'm4v'],
        'image' => ['jpg', 'jpeg', 'png', 'webp'],
    ];

    public static function rules(string $prefix): array
    {
        return [
            $prefix => ['sometimes', 'array', 'max:24'],
            $prefix.'.*.id' => ['sometimes', 'integer', 'min:1'],
            $prefix.'.*.type' => ['required', Rule::in(['text', 'audio', 'video', 'image'])],
            $prefix.'.*.body' => ['nullable', 'string', 'max:20000'],
            $prefix.'.*.file' => ['nullable', 'file', 'max:153600',
                'mimes:mp3,m4a,aac,ogg,wav,mp4,mov,webm,m4v,jpg,jpeg,png,webp'],
            $prefix.'.*.remove' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Apply an edited list without deleting untouched records.
     * Existing records may only be edited through their owning parent.
     */
    public function sync(Model $parent, array $rows, string $errorPrefix = 'media'): void
    {
        foreach ($rows as $index => $data) {
            $type = $data['type'];
            $existing = ! empty($data['id'])
                ? $parent->media()->findOrFail((int) $data['id'])
                : null;

            if (! empty($data['remove'])) {
                if ($existing) {
                    if ($existing->file_path) {
                        $this->oldFiles[] = $existing->file_path;
                    }
                    $existing->delete();
                }
                continue;
            }

            $body = trim((string) ($data['body'] ?? ''));
            $upload = $data['file'] ?? null;
            if (! $existing && ! $upload && $body === '') {
                continue; // Newly added but unfilled admin row.
            }

            $label = $errorPrefix.'.'.$index;
            if ($type === 'text' && $body === '') {
                throw ValidationException::withMessages([
                    $label.'.body' => 'Text content cannot be empty.',
                ]);
            }
            if ($type !== 'text' && ! $upload && (! $existing || ! $existing->file_path)) {
                throw ValidationException::withMessages([
                    $label.'.file' => 'Upload a file for this '.$type.' block.',
                ]);
            }
            if ($existing && $existing->type !== $type && ! $upload) {
                throw ValidationException::withMessages([
                    $label.'.type' => 'Remove the existing block and add a new type instead.',
                ]);
            }

            $path = $existing?->file_path;
            if ($upload instanceof UploadedFile) {
                if ($type === 'text') {
                    throw ValidationException::withMessages([
                        $label.'.file' => 'Text blocks cannot have uploaded files.',
                    ]);
                }
                // Validate the file against its selected media type, not only
                // the union of upload extensions in the form's request rules.
                Validator::make(['file' => $upload], [
                    'file' => ['required', 'file',
                        'mimes:'.implode(',', self::FILE_TYPES[$type]),
                        'max:'.($type === 'image' ? 5120 : ($type === 'audio' ? 102400 : 153600))],
                ])->validate();

                $path = $upload->store('content/'.$type, 'public');
                if (! $path) {
                    throw ValidationException::withMessages([
                        $label.'.file' => 'The media file could not be saved.',
                    ]);
                }
                $this->newFiles[] = $path;
                if ($existing?->file_path) $this->oldFiles[] = $existing->file_path;
            }
            if ($type === 'text' && $existing?->file_path) {
                $this->oldFiles[] = $existing->file_path;
                $path = null;
            }

            $position = $existing
                ? $existing->position
                : (int) $parent->media()->max('position') + 1;
            if ($existing) {
                $existing->update([
                    'body' => $body === '' ? null : $body,
                    'file_path' => $path,
                    'position' => $position,
                ]);
            } else {
                $parent->media()->create([
                    'type' => $type,
                    'body' => $body === '' ? null : $body,
                    'file_path' => $path,
                    'position' => $position,
                ]);
            }
        }
    }

    public function committed(): void
    {
        foreach (array_unique($this->oldFiles) as $path) {
            Storage::disk('public')->delete($path);
        }
        $this->newFiles = [];
        $this->oldFiles = [];
    }

    public function failed(): void
    {
        foreach (array_unique($this->newFiles) as $path) {
            Storage::disk('public')->delete($path);
        }
        $this->newFiles = [];
        $this->oldFiles = [];
    }
}
