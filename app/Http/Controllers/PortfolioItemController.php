<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PortfolioItem;
use App\Services\SupabaseStorageService;

class PortfolioItemController extends Controller
{
    /** Most items one "Add Portfolio Items" save can add */
    private const MAX_ITEMS_PER_SAVE = 10;

    /**
     * Adds one or more portfolio items in a single save. Every item is validated first; only
     * when all of them are valid are the images uploaded and the items created, so a mistake
     * in one item never leaves the others half-saved.
     */
    public function store(Request $request)
    {
        $validated = $request->validateWithBag('portfolio', [
            'items'                => 'required|array|min:1|max:' . self::MAX_ITEMS_PER_SAVE,
            'items.*.image'        => 'required|image|max:10240',
            'items.*.spec'         => 'required|string|max:50',
            'items.*.tag'          => 'required|string|max:50',
            'items.*.tag_custom'   => 'nullable|required_if:items.*.tag,__other__|string|max:50',
            'items.*.title'        => 'required|string|max:255',
            'items.*.description'  => 'required|string|max:1000',
        ], [
            'items.required'                    => 'Add at least one portfolio item.',
            'items.max'                         => 'You can add up to ' . self::MAX_ITEMS_PER_SAVE . ' items at a time.',
            'items.*.image.required'            => 'Item :position: please choose an image.',
            'items.*.image.uploaded'            => 'Item :position: the image could not be uploaded. Please choose an image up to 10MB.',
            'items.*.image.image'               => 'Item :position: the file must be an image (JPG, PNG, WEBP or GIF).',
            'items.*.image.max'                 => 'Item :position: the image must be 10MB or smaller.',
            'items.*.spec.required'             => 'Item :position: enter the capacity / badge.',
            'items.*.tag.required'              => 'Item :position: choose a category.',
            'items.*.tag_custom.required_if'    => 'Item :position: type the custom category.',
            'items.*.title.required'            => 'Item :position: enter a title.',
            'items.*.description.required'      => 'Item :position: enter a description.',
        ]);

        $storage   = app(SupabaseStorageService::class);
        $nextOrder = (PortfolioItem::max('sort_order') ?? -1) + 1;
        $rows      = [];

        // Upload every image first; the items are only created once all uploads succeeded
        foreach ($validated['items'] as $key => $item) {
            $tag = $item['tag'] === '__other__' ? trim((string) ($item['tag_custom'] ?? '')) : $item['tag'];

            $rows[] = [
                'image_url'   => $storage->upload($request->file("items.{$key}.image"), 'portfolio'),
                'spec'        => $item['spec'],
                'tag'         => $tag,
                'title'       => $item['title'],
                'description' => $item['description'],
                'sort_order'  => $nextOrder + count($rows),
                'status'      => 'active',
            ];
        }

        \DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                PortfolioItem::create($row);
            }
        });

        $count = count($rows);

        return redirect()
            ->route('admin.settings')
            ->with('active_tab', 'landing')
            ->with('success', $count === 1 ? 'Portfolio item added successfully.' : "{$count} portfolio items added successfully.");
    }

    public function update(Request $request, $id)
    {
        $item = PortfolioItem::findOrFail($id);

        // An item that has no picture yet must get one; otherwise a new file is optional (replaces the current one)
        $data = $request->validateWithBag('portfolioEdit', [
            'image'        => ($item->image_url ? 'nullable' : 'required') . '|image|max:10240',
            'icon'         => 'nullable|string|max:50',
            'spec'         => 'required|string|max:50',
            'tag'          => 'required|string|max:50',
            'title'        => 'required|string|max:255',
            'description'  => 'required|string|max:1000',
            'sort_order'   => 'nullable|integer|min:0',
        ], self::imageMessages());

        if ($request->hasFile('image')) {
            $data['image_url'] = app(SupabaseStorageService::class)->upload($request->file('image'), 'portfolio');
        }
        unset($data['image']);

        if (empty($data['sort_order'])) {
            $data['sort_order'] = $item->sort_order;
        }

        $item->update($data);

        return redirect()
            ->route('admin.settings')
            ->with('active_tab', 'landing')
            ->with('success', 'Portfolio item updated successfully.');
    }

    /** Plain-language image errors instead of Laravel's generic "failed to upload". */
    private static function imageMessages(): array
    {
        return [
            'image.required' => 'Please choose an image for this portfolio item.',
            'image.uploaded' => 'The image could not be uploaded. Please choose an image up to 10MB.',
            'image.image'    => 'The file must be an image (JPG, PNG, WEBP or GIF).',
            'image.max'      => 'The image must be 10MB or smaller.',
        ];
    }

    public function archive($id)
    {
        $item = PortfolioItem::findOrFail($id);
        $item->status = $item->status === 'archived' ? 'active' : 'archived';
        $item->save();

        $label = $item->status === 'archived' ? 'hidden' : 'restored';

        return redirect()
            ->route('admin.settings')
            ->with('active_tab', 'landing')
            ->with('success', "Portfolio item \"{$item->title}\" {$label} successfully.");
    }

    public function destroy($id)
    {
        $item = PortfolioItem::findOrFail($id);
        $item->delete();

        return redirect()
            ->route('admin.settings')
            ->with('active_tab', 'landing')
            ->with('success', 'Portfolio item deleted successfully.');
    }
}
