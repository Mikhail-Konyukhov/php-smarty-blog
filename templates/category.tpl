{extends file='layout.tpl'}

{block name=title}{$category.name}{/block}

{block name=content}
    <header class="category">
        <h1>{$category.name}</h1>
        <p class="category__description">{$category.description}</p>
    </header>

    {* Смена сортировки ведёт на первую страницу. Клик по активной сортировке меняет направление *}
    <p class="sort">
        Сортировка:
        <a class="sort__link{if $sort == 'date' || $sort == 'date-asc'} sort__link--active{/if}" href="/category/{$category.id}?sort={if $sort == 'date'}date-asc{else}date{/if}">по дате {if $sort == 'date-asc'}↑{else}↓{/if}</a>
        <a class="sort__link{if $sort == 'views' || $sort == 'views-asc'} sort__link--active{/if}" href="/category/{$category.id}?sort={if $sort == 'views'}views-asc{else}views{/if}">по просмотрам {if $sort == 'views-asc'}↑{else}↓{/if}</a>
    </p>

    {if $articles}
        <div class="grid">
            {foreach $articles as $card}
                {include file='partials/article-card.tpl'}
            {/foreach}
        </div>
    {else}
        <p>В этой категории пока нет статей.</p>
    {/if}

    {if $pages > 1}
        <nav class="pagination" aria-label="Страницы">
            {if $page > 1}
                <a class="pagination__item" href="/category/{$category.id}?sort={$sort}&amp;page={$page - 1}" aria-label="Предыдущая страница">←</a>
            {else}
                <span class="pagination__item">←</span>
            {/if}
            {foreach $links as $link}
                {if $link === null}
                    <span class="pagination__item">…</span>
                {elseif $link == $page}
                    <span class="pagination__item pagination__item--current" aria-current="page">{$link}</span>
                {else}
                    <a class="pagination__item" href="/category/{$category.id}?sort={$sort}&amp;page={$link}">{$link}</a>
                {/if}
            {/foreach}
            {if $page < $pages}
                <a class="pagination__item" href="/category/{$category.id}?sort={$sort}&amp;page={$page + 1}" aria-label="Следующая страница">→</a>
            {else}
                <span class="pagination__item">→</span>
            {/if}
        </nav>
    {/if}
{/block}
