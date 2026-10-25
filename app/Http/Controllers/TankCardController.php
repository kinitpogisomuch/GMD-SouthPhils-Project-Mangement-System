<?php

namespace App\Http\Controllers;

use App\Models\TankCard;
use App\Services\SupabaseStorageService;
use App\Support\TankCatalog;
use Illuminate\Http\Request;

/**
 * Settings → Landing Page → "Our Work" tank cards (Choose Your Tank, Get a Quotation). The tank types
 * themselves come from TankCatalog (each has its own quotation form), so cards are edited and hidden here,
 * not added or deleted.
 */
class TankCardController extends Controller
{
    private function definition(string $slug): array
    {
        $def = TankCatalog::find($slug);
        abort_unless($def, 404);

        return $def;
    }

    private function back(string $message)
    {
        return redirect()->route('admin.settings')->with('active_tab', 'landing')->with('success', $message);
    }

    private const IMAGE_MESSAGES = [
        'image.required' => 'Please choose a picture for the new tank card.',
        'image.uploaded' => 'The image could not be uploaded. Please choose an image up to 10MB.',
        'image.image'    => 'The file must be an image (JPG, PNG, WEBP or GIF).',
        'image.max'      => 'The image must be 10MB or smaller.',
    ];

    /** A tank type name nobody else uses (built-in or added), compared case-insensitively */
    private function nameRule(?string $ignoreSlug = null): \Closure
    {
        return function ($attribute, $value, $fail) use ($ignoreSlug) {
            $key = mb_strtolower(trim((string) $value));
            if (\Illuminate\Support\Str::slug($value) === '') {
                $fail('Use letters or numbers in the tank name.');
                return;
            }
            foreach (TankCatalog::all() as $slug => $def) {
                if ($slug !== $ignoreSlug && (mb_strtolower($def['type']) === $key || $slug === mb_substr(\Illuminate\Support\Str::slug($value), 0, 80))) {
                    $fail('There is already a tank card called "' . $def['type'] . '".');
                    return;
                }
            }
        };
    }

    /** Settings → "Add Tank Card": a new tank type clients can request with the general form */
    public function store(Request $request)
    {
        $data = $request->validateWithBag('tankCardAdd', [
            'type'           => ['required', 'string', 'max:100', $this->nameRule()],
            'image'          => 'required|image|max:10240',
            'blurb'          => 'required|string|max:500',
            'capacity_label' => 'nullable|string|max:50',
            'sizes'              => 'nullable|array|max:12',
            'sizes.*.capacity'   => 'nullable|string|max:60',
            'sizes.*.dimensions' => 'nullable|string|max:80',
            'sizes.*.price'      => 'nullable|string|max:20',   // estimated price per tank, e.g. "210,000"
        ], self::IMAGE_MESSAGES + [
            'type.required'  => 'Enter the tank name.',
            'blurb.required' => 'Enter a short description.',
            'sizes.max'              => 'Up to 12 sizes per tank.',
            'sizes.*.capacity.max'   => 'A capacity can be up to 60 characters.',
            'sizes.*.dimensions.max' => 'Dimensions can be up to 80 characters.',
        ]);

        $url = app(SupabaseStorageService::class)->upload($request->file('image'), 'tank-cards');
        if (!$url) {
            return redirect()->route('admin.settings')->with('active_tab', 'landing')->withInput()
                ->withErrors(['image' => 'The image could not be uploaded. Please check your connection and try again.'], 'tankCardAdd');
        }

        $type = trim($data['type']);
        TankCard::create([
            'slug'           => mb_substr(\Illuminate\Support\Str::slug($type), 0, 80),
            'is_custom'      => true,
            'type'           => $type,
            'image_url'      => $url,
            'blurb'          => trim($data['blurb']),
            'capacity_label' => trim((string) ($data['capacity_label'] ?? '')) ?: null,
            'sizes'          => TankCatalog::cleanSizes($data['sizes'] ?? []),
            'sort_order'     => null,   // added at the end; reorder later with Edit
            'is_visible'     => true,
        ]);

        return $this->back("\"{$type}\" added to the landing page.");
    }

    /** Only cards the admin added can be deleted — the built-in ones are hidden instead */
    public function destroy(string $slug)
    {
        $card = TankCard::find($slug);
        abort_unless($card && $card->is_custom, 404);

        $name = $card->type;
        $card->delete();   // requests already made keep their tank type name

        return $this->back("\"{$name}\" deleted from the landing page.");
    }

    public function update(Request $request, string $slug)
    {
        $def = $this->definition($slug);
        $isCustom = !empty($def['custom']);

        $data = $request->validateWithBag('tankCard', [
            'type'           => $isCustom ? ['required', 'string', 'max:100', $this->nameRule($slug)] : 'nullable',
            'capacity_label' => 'nullable|string|max:50',
            'image'          => 'nullable|image|max:10240',
            'reset_image'    => 'nullable|boolean',
            'blurb'          => ($isCustom ? 'required' : 'nullable') . '|string|max:500',
            'sort_order'     => 'nullable|integer|min:1|max:99',
            // standard sizes shown on the details page: "default" keeps the built-in list
            'sizes_mode'         => 'nullable|in:default,custom',
            'sizes'              => 'nullable|array|max:12',
            'sizes.*.capacity'   => 'nullable|string|max:60',
            'sizes.*.dimensions' => 'nullable|string|max:80',
            'sizes.*.price'      => 'nullable|string|max:20',   // estimated price per tank, e.g. "210,000"
        ], self::IMAGE_MESSAGES + [
            'sizes.max'                => 'Up to 12 sizes per tank.',
            'sizes.*.capacity.max'     => 'A capacity can be up to 60 characters.',
            'sizes.*.dimensions.max'   => 'Dimensions can be up to 80 characters.',
        ]);

        $card = TankCard::firstOrNew(['slug' => $slug]);
        if (($data['sizes_mode'] ?? null) === 'custom' || ($isCustom && $request->has('sizes_mode'))) {
            $card->sizes = TankCatalog::cleanSizes($data['sizes'] ?? []);
        } elseif (($data['sizes_mode'] ?? null) === 'default') {
            $card->sizes = null;
        }
        if ($isCustom) {
            // the slug stays the same, so links and earlier requests are unaffected by a rename
            $card->type           = trim($data['type']);
            $card->capacity_label = trim((string) ($data['capacity_label'] ?? '')) ?: null;
        }

        if ($request->hasFile('image')) {
            $url = app(SupabaseStorageService::class)->upload($request->file('image'), 'tank-cards');
            if (!$url) {
                return redirect()->route('admin.settings')->with('active_tab', 'landing')
                    ->withErrors(['image' => 'The image could not be uploaded. Please check your connection and try again.'], 'tankCard');
            }
            $card->image_url = $url;
        } elseif ($request->boolean('reset_image') && !$isCustom) {   // an added card has no default picture to go back to
            $card->image_url = null;
        }

        // Blank = use the default wording
        $card->blurb      = trim((string) ($data['blurb'] ?? '')) ?: null;
        $card->sort_order = $data['sort_order'] ?? $card->sort_order;
        if (!$card->exists) {
            $card->is_visible = true;
        }
        $card->save();

        return $this->back('"' . ($card->type ?: $def['type']) . '" card updated.');
    }

    /** Hide / show a card on the landing page and the client's quotation form */
    public function toggle(string $slug)
    {
        $def  = $this->definition($slug);
        $card = TankCard::firstOrNew(['slug' => $slug]);
        $card->is_visible = $card->exists ? !$card->is_visible : false;
        $card->save();

        return $this->back("\"{$def['type']}\" " . ($card->is_visible ? 'is shown again.' : 'is now hidden — clients can no longer request it.'));
    }
}
