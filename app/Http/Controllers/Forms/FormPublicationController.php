<?php

namespace App\Http\Controllers\Forms;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Services\Forms\FormPublishingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FormPublicationController extends Controller
{
    public function publish(Request $request, Form $form, FormPublishingService $publishing)
    {
        Gate::authorize('update', $form);
        $data = $request->validate(['revision' => ['required', 'integer', 'min:0']]);
        $version = $publishing->publish($form, $request->user(), $data['revision']);
        $url = route('public-forms.show', $form->slug);
        if ($request->expectsJson()) {
            return response()->json(['url' => $url, 'version' => $version->version_number, 'status' => 'published']);
        }

        return back()->with('status', 'Form published. Share the link below.')->with('published_url', $url);
    }

    public function unpublish(Form $form, FormPublishingService $publishing)
    {
        Gate::authorize('update', $form);
        $publishing->stop($form);

        return back()->with('status', 'Form unpublished. Its public link no longer accepts responses.');
    }

    public function archive(Form $form, FormPublishingService $publishing)
    {
        Gate::authorize('update', $form);
        $publishing->stop($form, true);

        return back()->with('status', 'Form archived. Historical responses are retained.');
    }
}
