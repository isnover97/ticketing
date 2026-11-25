<?php

namespace App\Http\Controllers;

use App\Models\HelpdeskRecord;
use Illuminate\Http\Request;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['search' => 'nullable|string|max:255', 'category' => 'nullable|string|max:255']);
        $query = HelpdeskRecord::ofKind('articles')->active();
        if ($request->filled('search')) {
            $term = '%'.$request->input('search').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('settings->body', 'like', $term));
        }
        if ($request->filled('category')) {
            $query->where('settings->category', $request->input('category'));
        }

        return view('helpdesk.knowledge', ['articles' => $query->orderBy('name')->paginate(20)->withQueryString(), 'article' => null]);
    }

    public function show(HelpdeskRecord $article)
    {
        abort_unless($article->kind === 'articles' && $article->is_active, 404);

        return view('helpdesk.knowledge', ['article' => $article, 'articles' => null]);
    }
}
