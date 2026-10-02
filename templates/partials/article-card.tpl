{* Карточка статьи. Переменная card — форма «карточка статьи» из ArticleRepository. *}
<article class="card">
    {* alt пустой: картинка-заглушка, заголовок ниже уже назван ссылкой *}
    <img class="card__image" src="{$card.image}" alt="" loading="lazy">
    <h3 class="card__title"><a href="/article/{$card.id}">{$card.title}</a></h3>
    <p class="card__description">{$card.description}</p>
    <p class="card__meta">
        <time datetime="{$card.published_at|date_format:'Y-m-d'}">{$card.published_at|date_format:'d.m.Y'}</time>
        · просмотров: {$card.views}
    </p>
</article>
