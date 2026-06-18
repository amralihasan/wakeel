@php
    $metaTitle = __('marketing.meta_contact_title');
    $metaDesc  = __('marketing.meta_contact_desc');
    $isAr      = app()->getLocale() === 'ar';
@endphp

<x-layouts.marketing :metaTitle="$metaTitle" :metaDesc="$metaDesc">

{{-- Hero --}}
<section class="bg-gradient-to-br from-brand-950 to-zinc-900 text-white py-20 text-center">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl sm:text-4xl font-bold mb-4">{{ __('marketing.contact_title') }}</h1>
        <p class="text-brand-200/80 text-lg">{{ __('marketing.contact_subtitle') }}</p>
    </div>
</section>

<section class="py-20 lg:py-28 bg-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-3 gap-12">

            {{-- Contact info sidebar --}}
            <div class="space-y-6">
                {{-- WhatsApp --}}
                <div class="bg-zinc-50 rounded-2xl border border-zinc-100 p-6">
                    <div class="w-10 h-10 rounded-xl bg-[#25D366]/10 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-[#25D366]" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    </div>
                    <h3 class="font-semibold text-zinc-900 mb-1">{{ __('marketing.contact_whatsapp_title') }}</h3>
                    <p class="text-zinc-500 text-sm mb-3">{{ __('marketing.contact_whatsapp_desc') }}</p>
                    <a
                        href="https://wa.me/201000000000"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#25D366] text-white text-sm font-semibold hover:brightness-110 transition-all"
                    >
                        {{ __('marketing.contact_whatsapp_btn') }}
                    </a>
                </div>

                {{-- Email --}}
                <div class="bg-zinc-50 rounded-2xl border border-zinc-100 p-6">
                    <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="font-semibold text-zinc-900 mb-1">{{ __('marketing.contact_email_title') }}</h3>
                    <p class="text-zinc-500 text-sm mb-2">{{ __('marketing.contact_email_desc') }}</p>
                    <a href="mailto:hello@wakeel.ai" class="text-brand-700 text-sm font-medium hover:underline">hello@wakeel.ai</a>
                </div>
            </div>

            {{-- Contact form --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl border border-zinc-200 shadow-sm p-8">
                    <h2 class="text-xl font-bold text-zinc-900 mb-6">{{ __('marketing.contact_form_title') }}</h2>

                    {{-- Success message --}}
                    @if(session('success'))
                        <div class="mb-6 flex items-start gap-3 bg-brand-50 border border-brand-200 rounded-xl px-4 py-3.5">
                            <svg class="w-5 h-5 text-brand-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <p class="text-brand-700 text-sm font-medium">{{ session('success') }}</p>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-6 flex items-start gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3.5">
                            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-red-600 text-sm">{{ $errors->first() }}</p>
                        </div>
                    @endif

                    <form action="{{ route('marketing.contact.store') }}" method="POST" class="space-y-5">
                        @csrf

                        <div class="grid sm:grid-cols-2 gap-5">
                            <div>
                                <label for="name" class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('marketing.contact_name') }} <span class="text-red-500">*</span></label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    value="{{ old('name') }}"
                                    placeholder="{{ __('marketing.contact_name_ph') }}"
                                    required
                                    class="w-full px-4 py-2.5 rounded-xl border border-zinc-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors @error('name') border-red-400 @enderror"
                                >
                                @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="company" class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('marketing.contact_company') }}</label>
                                <input
                                    type="text"
                                    id="company"
                                    name="company"
                                    value="{{ old('company') }}"
                                    placeholder="{{ __('marketing.contact_company_ph') }}"
                                    class="w-full px-4 py-2.5 rounded-xl border border-zinc-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                                >
                            </div>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-5">
                            <div>
                                <label for="email" class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('marketing.contact_email') }} <span class="text-red-500">*</span></label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    placeholder="{{ __('marketing.contact_email_ph') }}"
                                    required
                                    class="w-full px-4 py-2.5 rounded-xl border border-zinc-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors @error('email') border-red-400 @enderror"
                                >
                                @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('marketing.contact_phone') }}</label>
                                <input
                                    type="tel"
                                    id="phone"
                                    name="phone"
                                    value="{{ old('phone') }}"
                                    placeholder="{{ __('marketing.contact_phone_ph') }}"
                                    class="w-full px-4 py-2.5 rounded-xl border border-zinc-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                                >
                            </div>
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('marketing.contact_message') }} <span class="text-red-500">*</span></label>
                            <textarea
                                id="message"
                                name="message"
                                rows="5"
                                placeholder="{{ __('marketing.contact_message_ph') }}"
                                required
                                class="w-full px-4 py-2.5 rounded-xl border border-zinc-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors resize-none @error('message') border-red-400 @enderror"
                            >{{ old('message') }}</textarea>
                            @error('message')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <button
                            type="submit"
                            class="w-full px-6 py-3 rounded-xl bg-brand-700 text-white font-semibold text-sm hover:bg-brand-800 transition-colors shadow-xs"
                        >
                            {{ __('marketing.contact_submit') }}
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</section>

</x-layouts.marketing>
