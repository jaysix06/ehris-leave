<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Models\PopupMessage;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PopupMessageController extends Controller
{
    public function index(): Response
    {
        $popupMessages = PopupMessage::query()
            ->select(['id', 'message', 'link', 'status', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Utilities/PopUpManagement', [
            'popupMessages' => $popupMessages,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Convert empty link string to null
        $request->merge([
            'link' => $request->input('link') ?: null,
        ]);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'link' => ['nullable', 'string', 'max:500', 'url'],
            'status' => ['required', 'string', 'in:Active,Inactive'],
        ], [
            'message.required' => 'The message field is required.',
            'link.url' => 'The link must be a valid URL.',
            'status.in' => 'The status must be either Active or Inactive.',
        ]);

        // Create popup message - mutator will convert status string to integer
        $popupMessage = new PopupMessage;
        $popupMessage->message = $data['message'];
        $popupMessage->link = $data['link'];
        $popupMessage->status = $data['status']; // Mutator converts 'Active'/'Inactive' to 1/0
        $popupMessage->created_at = now();
        $popupMessage->save();

        $linkText = $data['link'] ? " (Link: {$data['link']})" : '';
        ActivityLogService::logCreate('Popup Message', substr($data['message'], 0, 50).'...'.$linkText);

        return back();
    }

    public function update(Request $request, PopupMessage $popupMessage): RedirectResponse
    {
        // Convert empty link string to null
        $request->merge([
            'link' => $request->input('link') ?: null,
        ]);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'link' => ['nullable', 'string', 'max:500', 'url'],
            'status' => ['required', 'string', 'in:Active,Inactive'],
        ], [
            'message.required' => 'The message field is required.',
            'link.url' => 'The link must be a valid URL.',
            'status.in' => 'The status must be either Active or Inactive.',
        ]);

        $oldMessage = substr($popupMessage->message, 0, 50).'...';
        $oldLink = $popupMessage->link ? " (Link: {$popupMessage->link})" : '';
        $oldStatus = $popupMessage->status;

        // Update popup message - set attributes individually so mutator is called
        $popupMessage->message = $data['message'];
        $popupMessage->link = $data['link'];
        $popupMessage->status = $data['status']; // Mutator converts 'Active'/'Inactive' to 1/0
        $popupMessage->save();

        $newMessage = substr($data['message'], 0, 50).'...';
        $newLink = $data['link'] ? " (Link: {$data['link']})" : '';
        $newStatus = $data['status'];

        $changes = [];
        if ($oldMessage !== $newMessage) {
            $changes[] = "message from '{$oldMessage}' to '{$newMessage}'";
        }
        if ($oldLink !== $newLink) {
            $changes[] = "link from '{$oldLink}' to '{$newLink}'";
        }
        if ($oldStatus !== $newStatus) {
            $changes[] = "status from '{$oldStatus}' to '{$newStatus}'";
        }

        ActivityLogService::logUpdate('Popup Message', implode(', ', $changes));

        return back();
    }

    public function destroy(PopupMessage $popupMessage): RedirectResponse
    {
        $message = substr($popupMessage->message, 0, 50).'...';
        $link = $popupMessage->link ? " (Link: {$popupMessage->link})" : '';

        $popupMessage->delete();

        ActivityLogService::logDelete('Popup Message', $message.$link);

        return back();
    }
}
