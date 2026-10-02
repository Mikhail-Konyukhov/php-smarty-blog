{extends file='layout.tpl'}

{block name=content}
    {foreach $categories as $category}
        <section class="block">
            <h2 class="block__title">{$category.name}</h2>
            <div class="grid">
                {foreach $category.articles as $card}
                    {include file='partials/article-card.tpl'}
                {/foreach}
            </div>
            <a class="button" href="/category/{$category.id}">Все статьи</a>
        </section>
    {foreachelse}
        <p>Статей пока нет.</p>
    {/foreach}
{/block}
