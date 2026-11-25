<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Stores and removes the private documents a member can have (Aadhaar images, payment proof, resume). */
class MemberDocumentService
{
    /** Validation rules for the four upload fields. All are optional here; the public form adds its own. */
    public static function rules(): array
    {
        $image = ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];

        return [
            'aadhaar_front' => $image,
            'aadhaar_back' => $image,
            'payment_screenshot' => $image,
            'resume' => ['nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240'],
        ];
    }

    public static function messages(): array
    {
        return [
            'aadhaar_front.max' => 'The Aadhaar front image must not be larger than 5 MB.',
            'aadhaar_back.max' => 'The Aadhaar back image must not be larger than 5 MB.',
            'payment_screenshot.max' => 'The payment screenshot must not be larger than 5 MB.',
            'resume.max' => 'The resume must not be larger than 10 MB.',
            'aadhaar_front.mimes' => 'The Aadhaar front must be a JPG, PNG or WebP image.',
            'aadhaar_back.mimes' => 'The Aadhaar back must be a JPG, PNG or WebP image.',
            'payment_screenshot.mimes' => 'The payment screenshot must be a JPG, PNG or WebP image.',
            'resume.mimes' => 'The resume must be a PDF file.',
            'resume.mimetypes' => 'The resume must be a PDF file.',
        ];
    }

    /**
     * Save every uploaded document to the private disk.
     *
     * @return array<string, string> database column => stored path
     */
    public function storeUploads(Request $request): array
    {
        $stored = [];

        foreach (Member::DOCUMENT_COLUMNS as $field => $column) {
            if ($request->hasFile($field)) {
                $stored[$column] = $request->file($field)->store('members/documents', 'local');
            }
        }

        return $stored;
    }

    /** @param array<int, string|null> $paths */
    public function delete(array $paths): void
    {
        foreach (array_filter($paths) as $path) {
            Storage::disk('local')->delete($path);
        }
    }
}
