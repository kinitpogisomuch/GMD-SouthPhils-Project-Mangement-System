<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Review;
use App\Models\Client;

class ReviewController extends Controller
{
    public function store(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);

        $clientEmail = session('email');
        $clientName  = $clientEmail
            ? Client::where('email', $clientEmail)->value('name')
            : null;

        if (!$clientName || $project->client !== $clientName) {
            abort(403, 'You do not have permission to review this project.');
        }

        if ($project->status !== 'completed') {
            return redirect()->route('client.projects')
                ->with('error', 'You can only review completed projects.');
        }

        $data = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        Review::updateOrCreate(
            ['project_id' => $project->id],
            [
                'client_name' => $clientName,
                'rating'      => $data['rating'],
                'comment'     => $data['comment'],
                'status'      => 'active',
            ]
        );

        return redirect()->route('client.projects')
            ->with('success', 'Thank you for your review!');
    }

    public function archive($id)
    {
        $review = Review::findOrFail($id);
        $review->status = $review->status === 'archived' ? 'active' : 'archived';
        $review->save();

        $label = $review->status === 'archived' ? 'hidden' : 'restored';

        return redirect()
            ->route('admin.settings')
            ->with('active_tab', 'landing')
            ->with('success', "Review {$label} successfully.");
    }

    public function destroy($id)
    {
        $review = Review::findOrFail($id);
        $review->delete();

        return redirect()
            ->route('admin.settings')
            ->with('active_tab', 'landing')
            ->with('success', 'Review deleted successfully.');
    }
}
