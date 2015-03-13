<?php

namespace W3docs\LogViewerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ANF\CommonBundle\API\ImageProvider;
use Symfony\Component\HttpFoundation\Request;
use Dubture\Monolog\Reader\LogReader;

/**
 * Description of SdcvWord
 *
 * @author Vazgen Manukyan <vazgen.manukyan@gmail.com>
 */
class LogViewerCommand
{

    /**
     *
     */
    protected function configure()
    {
        $this->setName('log:view')
            ->setDescription('show logs for given log file, filter by date and type. Also logs can be grouped')
            ->addArgument('log', InputArgument::REQUIRED, 'file that contains log line!')
            ->addOption('logger', null, InputOption::VALUE_OPTIONAL, 'this would be used to filter logger like "request", "security", ...')
            ->addOption('level', null, InputOption::VALUE_OPTIONAL, 'level of log, it might be DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY')
            ->addOption('start', null, InputOption::VALUE_OPTIONAL, 'start date')
            ->addOption('end', null, InputOption::VALUE_OPTIONAL, 'end date')
            ->addOption('group', null, InputOption::VALUE_NONE, 'group logs by message')
            ->addOption('having', null, InputOption::VALUE_OPTIONAL, 'this is used for with group only, it will filter logs that have less then given number');

    }

    /**
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // get arguments
        $levels = array('DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY');

        // add path to log dir
        $log_dir = $this->getContainer()->getParameter('kernel.logs_dir');
        $file = $input->getArgument('log');
        $file = $log_dir . '/' . $file;

        $logger = $input->getOption('logger');
        $level = $input->getOption('level');
        $start = $input->getOption('start');
        $end = $input->getOption('end');
        $group = $input->getOption('group');
        $having = $input->getOption('having');

        //
        $lines = count(file($file));

        // start progress
        $progress = $this->getApplication()->getHelperSet()->get('progress');

        // final logs
        $logs = array();

        // find the word
        if (file_exists($file)) {
            // display info
            $output->writeln("<info>Scanning logs: ");
            // default values
            if ($start) {
                $start = new \DateTime($start);
                $output->writeln("start: " . $start->format('Y-m-d H:i:s'));
            }

            if ($end) {
                $end = new \DateTime($end);
                $output->writeln("end: " . $end->format('Y-m-d H:i:s'));
            }

            if($level){
                if(in_array(strtoupper($level), $levels)){

                    $level = array_slice($levels, array_search(strtoupper($level), $levels));
                    $ls = implode(', ', $level);
                    $output->write("levels: $ls \n");

                }
                else{
                    $level = null;
                }
            }

            if($logger){
                $logger = strtolower($logger);
                $output->writeln("logger: {$logger}");
            }

            $output->writeln("</info>");

            // processing

            // report about current word
            $reader = new LogReader($file, 0);
            $progress->start($output, $lines);

            foreach ($reader as $log) {
                $progress->advance();

                // filter by level
                if($level && (!isset($log['level']) ||  !in_array($log['level'], $level))){
                    continue;
                }

                // filter by start date
                if($start && array_key_exists('date', $log) && $start > $log['date']){
                    continue;
                }

                // filter by end date
                if($end && array_key_exists('date', $log) && $end < $log['date']){
                    continue;
                }

                // filter by logger
                if($logger && array_key_exists('logger', $log) && $logger != $log['logger']){
                    continue;
                }

                $logs[] = $log;
            }

            $output->writeln("\n " . count($logs) . " logs.  Done!");

            if($group){
                // lets group
                $grouped_logs = array(
                    'EMERGENCY' => array(),
                    'ALERT' => array(),
                    'CRITICAL' => array(),
                    'ERROR' => array(),
                    'WARNING' => array(),
                    'NOTICE' => array(),
                    'INFO' => array(),
                    'DEBUG' => array(),
                    'EMPTY' => array()
                );

                $total = 0;

                // group logs
                foreach($logs as $log){
                    $level = (isset($log['level'])) ? $log['level'] : 'EMPTY';

                    // create new if does not exist
                    if(!array_key_exists($log['message'], $grouped_logs[$level])){
                        $grouped_logs[$level][$log['message']] = 0;
                    }

                    $grouped_logs[$level][$log['message']]++;
                }

                // print logs
                foreach($grouped_logs as $levl => $ls){

                    // do not show empty logs
                    if(count($ls) < 1){
                        continue;
                    }

                    $output->writeln("<comment>$levl</comment>");
                    arsort($ls);

                    foreach($ls as $message => $count){

                        if($having && $count < $having){
                            continue;
                        }

                        $total += $count;
                        $output->writeln("[$count]\t{$message}");
                    }
                }

                // write total
                $output->writeln("<comment>total: {$total}</comment>");
            } else{

                // print logs
                foreach($logs as $l){
                    $date = (isset($l['date'])) ? $l['date']->format('Y-m-d H:i:s') : '';
                    $lg = (isset($l['logger'])) ? $l['logger']: '';
                    $lvl = (isset($l['level'])) ? $l['level']: '';
                    $message = (isset($l['message'])) ? $l['message']: '';

                    $output->writeln("[{$date}] {$lg}.{$lvl}: {$message}");
                }

            }

        } else {
            $output->writeln("file '$file' does not exist!");
        }
    }

    /**
     * @param $router
     * @param $routeName
     * @param $word
     * @param $isReg
     * @param $content
     * @param $output
     */
    protected function checkContent($router, $routeName, $word, $isReg, $content, $output)
    {
        $url = $router->generate($routeName, array('word' => $word), true);

        // grab
        $html = $this->curl->get($url);
        if ($isReg) {
            $res = (preg_match($content, $html)) ? true : false;
        } else {
            $res = (strpos($html, $content) !== false) ? true : false;
        }

        if ($res) {
            $output->writeln("\n$url");
        }
    }
}
