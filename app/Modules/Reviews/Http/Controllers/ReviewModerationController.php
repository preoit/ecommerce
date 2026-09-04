<?php
namespace App\Modules\Reviews\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\Inventories\Products\Models\ProductQuestion;
use App\Modules\Inventories\Products\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class ReviewModerationController extends Controller
{
    public function index(): Response { return Inertia::render('app/modules/reviews/pages/Index',['reviews'=>ProductReview::with('product:id,title')->latest()->paginate(20),'questions'=>ProductQuestion::with('product:id,title')->latest()->paginate(20,['*'],'questions_page')]); }
    public function review(Request $request, ProductReview $review): RedirectResponse { $data=$request->validate(['status'=>['required','in:pending,approved,rejected,spam'],'admin_reply'=>['nullable','string','max:5000']]);$review->update($data);return back()->with('success','Review updated.'); }
    public function question(Request $request, ProductQuestion $question): RedirectResponse { $data=$request->validate(['status'=>['required','in:pending,approved,rejected,spam'],'answer'=>['nullable','string','max:5000']]);$question->update([...$data,'answered_at'=>filled($data['answer']??null)?now():null]);return back()->with('success','Question updated.'); }
    public function destroyReview(ProductReview $review): RedirectResponse { $review->delete();return back()->with('success','Review deleted.'); }
    public function destroyQuestion(ProductQuestion $question): RedirectResponse { $question->delete();return back()->with('success','Question deleted.'); }
}
