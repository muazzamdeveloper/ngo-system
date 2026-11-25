@php($posts = \App\Models\Post::published()->with('categories:id,name')->latest('published_at')->limit($section->item_limit ?: 6)->get())
@if ($posts->isNotEmpty())
    <section class="hs hs-tint">
        <div class="hs-inner">
            @include('home.sections._head')

            <div class="news-grid">
                @foreach ($posts as $post)
                    <article class="news-card">
                        <div class="news-image">
                            @if ($post->imageUrl()) <img src="{{ $post->imageUrl() }}" alt="" loading="lazy"> @endif
                        </div>
                        <div class="news-body">
                            <div class="news-meta">
                                <span>{{ ($post->published_at ?? $post->created_at)->format('d M Y') }}</span>
                                @if ($post->categories->isNotEmpty())
                                    <span class="news-cat">{{ $post->categories->first()->name }}</span>
                                @endif
                            </div>
                            <h3>{{ $post->title }}</h3>
                            <p>{{ $post->excerptText(140) }}</p>
                        </div>
                    </article>
                @endforeach
            </div>

            @if (filled($section->link_label) && filled($section->link_url))
                <p class="hs-more"><a class="btn" href="{{ $section->link_url }}">{{ $section->link_label }}</a></p>
            @endif
        </div>
    </section>
@endif
