<?php

namespace elish\helpers;

class PageHelper
{
    /**
     * 返回一根分页条代码
     *
     * @param ListView $listView
     * @return string
     */
    public static function render(ListView $listView): string
    {

        return render('common/page', [
            'current' => $listView->getCurrent(),
            'pageSize' => $listView->getPageKey(),
            'pageKey' => $listView->getPageKey(),
            'offset' => $listView->getOffset(),
            'startRecord' => $listView->getStartRecord(),
            'endRecord' => $listView->getEndRecord(),
            'totalRecords' => $listView->getTotalRecords(),
            'totalPages' => $listView->getTotalPages(),
            'adjacent' => $listView->getAdjacent(),
        ]);
    }
}