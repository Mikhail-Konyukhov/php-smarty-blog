{extends file='layout.tpl'}

{block name=title}{$article.title}{/block}

{block name=content}
    <article class="article">
        {* alt пустой: картинка-заглушка, заголовок ниже *}
        <img class="article__image" src="{$article.image}" alt="">
        <h1 class="article__title">{$article.title}</h1>
        <p class="article__description">{$article.description}</p>
        <p class="article__meta">
            <time datetime="{$article.published_at|date_format:'Y-m-d'}">{$article.published_at|date_format:'d.m.Y'}</time>
            · просмотров: {$article.views}
        </p>
        <p class="article__categories">
            {foreach $categories as $category}
                <a href="/category/{$category.id}">{$category.name}</a>
            {/foreach}
        </p>
        {* Обычный текст, абзацы разделены пустой строкой: их показывает CSS white-space: pre-line *}
        <div class="article__body">{$article.body}</div>
    </article>

    {if $similar}
        <section class="block">
            <h2 class="block__title">Похожие статьи</h2>
            <div class="grid">
                {foreach $similar as $card}
                    {include file='partials/article-card.tpl'}
                {/foreach}
            </div>
        </section>
    {/if}
{/block}
