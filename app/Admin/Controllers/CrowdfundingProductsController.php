<?php

namespace App\Admin\Controllers;

use App\Models\CrowdfundingProduct;
use App\Models\Product;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CrowdfundingProductsController extends CommonProductsController
{
    public function getProductType()
    {
        return Product::TYPE_CROWDFUNDING;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Product::findOrFail($id));

        $show->id('Id');
        $show->type('Type');
        $show->category_id('Category id');
        $show->title('Title');
        $show->description('Description');
        $show->image('Image');
        $show->on_sale('On sale');
        $show->rating('Rating');
        $show->sold_count('Sold count');
        $show->review_count('Review count');
        $show->price('Price');
        $show->created_at('Created at');
        $show->updated_at('Updated at');

        return $show;
    }

    protected function customForm(Form $form)
    {
        $form->text('crowdfunding.target_amount','众筹目标金额')->rules('required|numeric|min:0.01');
        $form->datetime('crowdfunding.end_at','众筹结束时间')->rules('required|date');
    }

    protected function customGrid(Grid $grid)
    {
        $grid->id('ID')->sortable();
        $grid->title('商品名称');
        $grid->on_sale('已上架')->display(function ($value) {
            return $value ? '是' : '否';
        });
        $grid->price('价格');
        $grid->column('crowdfunding.target_amount','目标金额');
        $grid->column('crowdfunding.end_at','结束时间');
        $grid->column('crowdfunding.total_amount','目前金额');
        $grid->column('crowdfunding.status','状态')->display(function ($value) {
            return CrowdfundingProduct::$statusMap[$value];
        });
    }

}
