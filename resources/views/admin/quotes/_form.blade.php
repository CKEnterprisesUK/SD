@if ($errors->any())
    <div class="mb-6 border border-red-700 bg-red-50 px-4 py-3 text-sm text-red-900">
        <p class="font-semibold mb-2">There is a problem with the form.</p>

        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $selectedCustomerId = old(
        'customer_id',
        $quote?->customer_id ?? $selectedCustomer?->id ?? ''
    );

    $status = old('status', $quote?->status ?? 'draft');

    /*
     * The controller may still pass $users.
     * This form now filters that list so only active admin users appear.
     */
    $assignableAdmins = collect($users ?? [])
        ->filter(function ($user) {
            return ($user->role ?? null) === 'admin'
                && in_array(($user->status ?? 'active'), ['active', null], true);
        })
        ->sortBy('name')
        ->values();

    $selectedAssignedUserId = old('assigned_user_id', $quote?->assigned_user_id);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-8">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <section class="border border-gray-300 bg-white p-6">
        <h2 class="text-lg font-semibold mb-4">Quote details</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="customer_id" class="block text-sm font-semibold mb-2">
                    Customer <span class="text-red-700">*</span>
                </label>

                <select id="customer_id"
                        name="customer_id"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        required>
                    <option value="">Select customer</option>

                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) $selectedCustomerId === (string) $customer->id)>
                            {{ $customer->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="assigned_user_id" class="block text-sm font-semibold mb-2">
                    Assigned admin
                </label>

                <select id="assigned_user_id"
                        name="assigned_user_id"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none">
                    <option value="">Unassigned</option>

                    @foreach ($assignableAdmins as $assignableAdmin)
                        <option value="{{ $assignableAdmin->id }}" @selected((string) $selectedAssignedUserId === (string) $assignableAdmin->id)>
                            {{ $assignableAdmin->name }}
                        </option>
                    @endforeach
                </select>

            

                @if ($assignableAdmins->isEmpty())
                    <p class="text-xs text-red-700 mt-2">
                        No active admin users are currently available.
                    </p>
                @endif
            </div>

            <div>
                <label for="title" class="block text-sm font-semibold mb-2">
                    Quote title <span class="text-red-700">*</span>
                </label>

                <input id="title"
                       name="title"
                       type="text"
                       value="{{ old('title', $quote?->title) }}"
                       class="w-full border border-gray-400 px-4 py-3 rounded-none"
                       placeholder="e.g. Rear extension and plastering works"
                       required>
            </div>

            <div>
                <label for="status" class="block text-sm font-semibold mb-2">
                    Status <span class="text-red-700">*</span>
                </label>

                <select id="status"
                        name="status"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        required>
                    <option value="draft" @selected($status === 'draft')>Draft</option>
                    <option value="survey_in_progress" @selected($status === 'survey_in_progress')>Survey in progress</option>
                    <option value="survey_completed" @selected($status === 'survey_completed')>Survey completed</option>
                    <option value="ai_compiled" @selected($status === 'ai_compiled')>AI compiled</option>
                    <option value="sent" @selected($status === 'sent')>Sent</option>
                    <option value="accepted" @selected($status === 'accepted')>Accepted</option>
                    <option value="declined" @selected($status === 'declined')>Declined</option>
                    <option value="expired" @selected($status === 'expired')>Expired</option>
                    <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
                </select>
            </div>

            <div>
                <label for="valid_until" class="block text-sm font-semibold mb-2">
                    Valid until
                </label>

                <input id="valid_until"
                       name="valid_until"
                       type="date"
                       value="{{ old('valid_until', $quote?->valid_until?->format('Y-m-d')) }}"
                       class="w-full border border-gray-400 px-4 py-3 rounded-none">
            </div>

            <div class="md:col-span-2">
                <label for="site_address" class="block text-sm font-semibold mb-2">
                    Site address
                </label>

                <textarea id="site_address"
                          name="site_address"
                          rows="4"
                          class="w-full border border-gray-400 px-4 py-3 rounded-none"
                          placeholder="Leave blank to use the customer address">{{ old('site_address', $quote?->site_address) }}</textarea>
            </div>

            <div class="md:col-span-2">
                <label for="summary" class="block text-sm font-semibold mb-2">
                    Summary
                </label>

                <textarea id="summary"
                          name="summary"
                          rows="4"
                          class="w-full border border-gray-400 px-4 py-3 rounded-none"
                          placeholder="Short internal summary of the quote">{{ old('summary', $quote?->summary) }}</textarea>
            </div>

            <div class="md:col-span-2">
                <label for="internal_notes" class="block text-sm font-semibold mb-2">
                    Internal notes
                </label>

                <textarea id="internal_notes"
                          name="internal_notes"
                          rows="4"
                          class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ old('internal_notes', $quote?->internal_notes) }}</textarea>
            </div>
        </div>
    </section>

    @if ($quote)
        <section class="border border-gray-300 bg-white p-6">
            <h2 class="text-lg font-semibold mb-4">Customer pack draft sections</h2>

            <p class="text-sm text-gray-600 mb-4">
                These sections will later form the customer-facing quote PDF. For now, they can be manually edited.
            </p>

            <div class="space-y-4">
                <div>
                    <label for="final_customer_message" class="block text-sm font-semibold mb-2">
                        Customer message
                    </label>

                    <textarea id="final_customer_message"
                              name="final_customer_message"
                              rows="5"
                              class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ old('final_customer_message', $quote->final_customer_message) }}</textarea>
                </div>

                <div>
                    <label for="final_scope" class="block text-sm font-semibold mb-2">
                        Scope of works
                    </label>

                    <textarea id="final_scope"
                              name="final_scope"
                              rows="6"
                              class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ old('final_scope', $quote->final_scope) }}</textarea>
                </div>

                <div>
                    <label for="final_timeline" class="block text-sm font-semibold mb-2">
                        Estimated timeline
                    </label>

                    <textarea id="final_timeline"
                              name="final_timeline"
                              rows="4"
                              class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ old('final_timeline', $quote->final_timeline) }}</textarea>
                </div>

                <div>
                    <label for="final_assumptions" class="block text-sm font-semibold mb-2">
                        Assumptions
                    </label>

                    <textarea id="final_assumptions"
                              name="final_assumptions"
                              rows="4"
                              class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ old('final_assumptions', $quote->final_assumptions) }}</textarea>
                </div>

                <div>
                    <label for="final_exclusions" class="block text-sm font-semibold mb-2">
                        Exclusions
                    </label>

                    <textarea id="final_exclusions"
                              name="final_exclusions"
                              rows="4"
                              class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ old('final_exclusions', $quote->final_exclusions) }}</textarea>
                </div>

                <div>
                    <label for="final_terms" class="block text-sm font-semibold mb-2">
                        Terms
                    </label>

                    <textarea id="final_terms"
                              name="final_terms"
                              rows="4"
                              class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ old('final_terms', $quote->final_terms) }}</textarea>
                </div>
            </div>
        </section>
    @endif

    <div class="flex items-center gap-4">
        <button type="submit"
                class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
            {{ $quote ? 'Save quote' : 'Create quote' }}
        </button>

        <a href="{{ $quote ? route('admin.quotes.show', $quote) : route('admin.quotes.index') }}"
           class="text-sm underline">
            Cancel
        </a>
    </div>
</form>