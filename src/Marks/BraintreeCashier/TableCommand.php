<?php

namespace Laravel\Cashier;

use Illuminate\Console\Command;

class TableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'braintree-cashier:table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Cashier table to work with braintree-cashier package.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
    }
}
