<?php

namespace App\Console\Commands\Cron;

use App\Jobs\RefundCrowdfundingOrders;
use App\Models\CrowdfundingProduct;
use App\Models\Order;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FinishCrowdfunding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:finish-crowdfunding';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '结束众筹';

    public function handle()
    {
        CrowdfundingProduct::query()
            ->with(['product'])
            ->where('end_at','<=',Carbon::now())
            ->where('status',CrowdfundingProduct::STATUS_FUNDING)
            ->get()
            ->each(function(CrowdfundingProduct $crowdfunding){
                if($crowdfunding->target_amount > $crowdfunding->total_amount){
                    $this->crowdfundingFailed($crowdfunding);
                }else{
                    $this->crowdfundingSucceed($crowdfunding);
                }
            });

    }

    protected function crowdfundingSucceed(CrowdfundingProduct $crowdfunding){
        $crowdfunding->update(['status' => CrowdfundingProduct::STATUS_SUCCESS]);
    }

    protected function crowdfundingFailed(CrowdfundingProduct $crowdfunding){
        $crowdfunding->update(['status' => CrowdfundingProduct::STATUS_FAIL]);
        dispatch(new RefundCrowdfundingOrders($crowdfunding));
    }
}
