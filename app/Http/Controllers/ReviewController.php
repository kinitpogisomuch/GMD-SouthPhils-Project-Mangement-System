<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Review;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function store(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);

        // Ownership check compares by client_id (stable) so a client renaming
        // themselves never locks them out of reviewing their own project.
        // Falls back to name-matching for the rare project predating the link.
        if ($project->client_id) {
            $ownsProject = (int) $project->client_id === (int) session('user_id');
        } else {
            $clientEmail = session('email');
            $lookupName  = $clientEmail ? Client::where('email', $clientEmail)->value('name') : null;
            $ownsProject = $lookupName && $project->client === $lookupName;
        }

        if (!$ownsProject) {
            abort(403, 'You do not have permission to review this project.');
        }

        $clientName = $project->live_client_name;

        if ($project->status !== 'completed') {
            return redirect()->route('client.projects')
                ->with('error', 'You can only review completed projects.');
        }

        $validator = Validator::make($request->all(), [
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ], [
            'rating.required' => 'Please choose a star rating.',
            'rating.integer'  => 'Please choose a star rating.',
            'rating.min'      => 'Please choose a star rating.',
            'rating.max'      => 'Please choose a star rating.',
            'comment.required' => 'Please write a short review.',
            'comment.max'      => 'Your review can be at most 1,000 characters.',
        ]);

        // The review form lives in a pop-up on the projects list, so a plain validation redirect would
        // just reload the page with the pop-up closed and no message. Send the errors and what was typed
        // back, plus which project it was for, so the pop-up reopens with the problem shown.
        if ($validator->fails()) {
            return redirect()->route('client.projects')
                ->withErrors($validator, 'review')
                ->withInput()
                ->with('review_project_id', $project->id);
        }
        $data = $validator->validated();

        $review = Review::updateOrCreate(
            ['project_id' => $project->id],
            [
                'client_name'  => $clientName,
                'rating'       => $data['rating'],
                'comment'      => $data['comment'],
                'status'       => 'active',
            ]
        );

        // is_anonymous is a PostgreSQL boolean column, and this connection uses emulated prepared statements,
        // which send a PHP true/false as the number 1/0 — PostgreSQL rejects that ("column is of type boolean but
        // expression is of type integer"), so saving a review used to fail outright. Writing it as a real SQL
        // true/false literal avoids the mismatch.
        Review::whereKey($review->id)->update([
            'is_anonymous' => DB::raw($request->boolean('hide_name') ? 'true' : 'false'),
        ]);

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
