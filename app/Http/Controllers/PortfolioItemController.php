<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PortfolioItem;
use App\Services\SupabaseStorageService;

class PortfolioItemController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validateWithBag('portfolio', [
            'image'       => 'required|image|max:10240',
            'icon'        => 'nullable|string|max:50',
            'spec'        => 'required|string|max:50',
            'tag'         => 'required|string|max:50',
            'title'       => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'sort_order'  => 'nullable|integer|min:0',
        ], self::imageMessages());

        $data['image_url'] = app(SupabaseStorageService::class)->upload($request->file('image'), 'portfolio');
        unset($data['image']);

        if (empty($data['sort_order'])) {
            $data['sort_order'] = (PortfolioItem::max('sort_order') ?? -1) + 1;
        }

        $data['status'] = 'active';

        PortfolioItem::create($data);

        return redirect()
            ->route('admin.settings')
            ->with('active_tab', 'landing')
            ->with('success', 'Portfolio item added successfully.');
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
