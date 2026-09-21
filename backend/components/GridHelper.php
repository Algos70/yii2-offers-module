<?php

declare(strict_types=1);

namespace backend\components;

use common\widgets\HelpTip;
use yii\data\Sort;
use yii\db\ActiveRecord;
use yii\grid\ActionColumn;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Shared presentation bits for the admin grids, so the casino and offer lists
 * cannot drift apart visually.
 */
final class GridHelper
{
    /**
     * GridView options: the table sits inside a card, the summary and pager in
     * its footer, and the filter row keeps its own muted background.
     *
     * @return array<string, mixed>
     */
    public static function options(string $emptyText): array
    {
        return [
            'options' => ['class' => 'grid-view card border-0 shadow-sm overflow-hidden'],
            'tableOptions' => ['class' => 'table table-hover align-middle mb-0'],
            'headerRowOptions' => ['class' => 'text-secondary text-uppercase small'],
            'layout' => '<div class="table-responsive">{items}</div>'
                . '<div class="card-footer d-flex flex-wrap justify-content-between '
                . 'align-items-center gap-2 py-2">{summary}{pager}</div>',
            'summaryOptions' => ['class' => 'text-secondary small mb-0'],
            'pager' => [
                'options' => ['class' => 'pagination pagination-sm mb-0'],
                'linkOptions' => ['class' => 'page-link'],
                'disabledListItemSubTagOptions' => ['class' => 'page-link'],
            ],
            'emptyText' => '<div class="p-4 text-center text-secondary">'
                . Html::encode($emptyText) . '</div>',
            'emptyTextOptions' => ['class' => 'p-0'],
        ];
    }

    /**
     * Action column with visible Bootstrap 5 icons.
     *
     * Yii's defaults emit `glyphicon` markup from Bootstrap 3, which renders as
     * an empty span here, so the buttons are rebuilt explicitly.
     *
     * @template T of ActiveRecord
     * @param callable(T): string $confirmText delete confirmation built per row
     * @return array<string, mixed>
     */
    public static function actionColumn(callable $confirmText): array
    {
        return [
            'class' => ActionColumn::class,
            'header' => 'Actions',
            'headerOptions' => ['class' => 'text-end', 'style' => 'width:8rem'],
            'contentOptions' => ['class' => 'text-end text-nowrap'],
            'urlCreator' => static fn (string $action, ActiveRecord $model): string
                => Url::to([$action, 'id' => $model->getPrimaryKey()]),
            'buttons' => [
                'view' => static fn (string $url): string => Html::a(
                    '<i class="bi bi-eye"></i>',
                    $url,
                    ['class' => 'btn btn-sm btn-outline-secondary', 'title' => 'View', 'aria-label' => 'View'],
                ),
                'update' => static fn (string $url): string => Html::a(
                    '<i class="bi bi-pencil"></i>',
                    $url,
                    ['class' => 'btn btn-sm btn-outline-primary', 'title' => 'Update', 'aria-label' => 'Update'],
                ),
                'delete' => static fn (string $url, ActiveRecord $model): string => Html::a(
                    '<i class="bi bi-trash"></i>',
                    $url,
                    [
                        'class' => 'btn btn-sm btn-outline-danger',
                        'title' => 'Delete',
                        'aria-label' => 'Delete',
                        'data-confirm' => $confirmText($model),
                        'data-method' => 'post',
                    ],
                ),
            ],
            'buttonOptions' => [],
        ];
    }

    /**
     * Column header carrying a "?" marker, with sorting left intact.
     *
     * `DataColumn` builds its own header - a sort link when the attribute is
     * sortable, a plain label otherwise - and setting `header` replaces the
     * whole thing. So the sort link is rebuilt here rather than dropped.
     *
     * @param Sort|false $sort the data provider's sort, or false when disabled
     */
    public static function helpHeader(
        Sort|false $sort,
        string $attribute,
        string $label,
        string $term,
    ): string {
        $header = $sort !== false && $sort->hasAttribute($attribute)
            ? $sort->link($attribute, ['label' => Html::encode($label)])
            : Html::encode($label);

        return $header . ' ' . HelpTip::for($term);
    }

    /**
     * Coloured badge, used for the enum columns and the active flag.
     */
    public static function badge(string $label, string $variant): string
    {
        return Html::tag(
            'span',
            Html::encode($label),
            ['class' => "badge rounded-pill text-bg-$variant fw-normal"],
        );
    }

    /**
     * Placeholder shown where a nullable column has no value.
     */
    public static function blank(string $text = '—'): string
    {
        return Html::tag('span', $text, ['class' => 'text-secondary']);
    }
}
