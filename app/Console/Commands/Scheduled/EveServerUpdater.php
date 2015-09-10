<?php
/*
The MIT License (MIT)

Copyright (c) 2014 eve-seat

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

namespace ineluctable\Console\Commands\Scheduled;

use Illuminate\Console\Command;

use Illuminate\Foundation\Bus\DispatchesJobs;
use ineluctable\Jobs\Scheduled\ScheduleUpdateEveServer;

use ineluctable\EveApi;
use ineluctable\EveApi\Account;
use ineluctable\Services\Settings\SettingHelper as Settings;

class EveServerUpdater extends Command
{
    use DispatchesJobs;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'seatscheduled:api-update-server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates Eve Server Related Information.';



    /**
     * Is this command enbaled.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return Settings::getSetting('seatscheduled_server') == 'true' ? true : false ;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        // Log what we are going to do in the laravel.log file
        \Log::info('Started command ' . $this->name, array('src' => __CLASS__));

        //call the job to check server status
        $job = new ScheduleUpdateEveServer();
        $this->dispatch($job);


    }
}
