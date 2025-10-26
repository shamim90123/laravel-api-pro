<?php
namespace App\Http\Controllers\Api\Leads;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadComment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LeadCommentController extends Controller
{
    // GET /leads/{lead}/comments
    public function index(Lead $lead, Request $request): JsonResponse
    {
        $perPage  = (int) $request->get('per_page', 10);
        $comments = $lead->comments()->with(['user:id,name', 'contact:id,name'])->latest()->paginate($perPage);
        return response()->json($comments);
    }

    // POST /leads/{lead}/comments
    public function store(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:5000'],
        ]);

        $userId = auth()->id() ?? $request->get('user_id');
        if (!$userId) return response()->json(['message' => 'User not identified'], 422);

        $comment = LeadComment::create([
            'lead_id' => $lead->id,
            'user_id' => $userId,
            'comment' => $validated['comment'],
        ])->load('user:id,name');

        return response()->json(['message' => 'Comment added', 'comment' => $comment], 201);
    }

    // DELETE /leads/{lead}/comments/{comment}
    public function destroy(Lead $lead, LeadComment $comment): JsonResponse
    {
        if ($comment->lead_id !== $lead->id) {
            return response()->json(['message' => 'Comment does not belong to this lead'], 422);
        }
        $comment->delete();
        return response()->json(['message' => 'Comment deleted']);
    }

    // PATCH/PUT /leads/{lead}/comments/{comment}
    public function update(Request $request, Lead $lead, LeadComment $comment): JsonResponse
    {
        // Ensure the comment belongs to the given lead
        if ($comment->lead_id !== $lead->id) {
            return response()->json(['message' => 'Comment does not belong to this lead'], 422);
        }

        // Optional: only author (or admins) can edit. Adjust as needed.
        // If you have policies: $this->authorize('update', $comment);
        $authId = auth()->id();
        if ($authId && $comment->user_id !== $authId && !auth()->user()?->can('leads.comments.edit.any')) {
            return response()->json(['message' => 'Not authorized to edit this comment'], 403);
        }

        // Validate input
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:5000'],
        ]);

        // If unchanged, just return current object
        if (trim($validated['comment']) === trim($comment->comment)) {
            return response()->json([
                'message' => 'No changes',
                'comment' => $comment->load('user:id,name'),
            ], 200);
        }

        // Update
        $comment->comment = $validated['comment'];
        $comment->save();

        // Return the updated comment with user relation
        return response()->json([
            'message' => 'Comment updated',
            'comment' => $comment->load('user:id,name'),
        ], 200);
    }

}
