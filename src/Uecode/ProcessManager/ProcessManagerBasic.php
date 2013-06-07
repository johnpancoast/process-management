<?php
/**
 * @author John Pancoast <shideon@gmail.com>
 */

namespace Uecode\ProcessManager;

use Symfony\Component\Process\Process;

class ProcessManagerBasic
{
	/**
	 * @var array commands we'll run
	 *
	 * @access private
	 */
	private $commands = array();

	/**
	 * @var bool Do we send a SIGKILL signal w/ the kill commands.
	 *
	 * @access private
	 */
	private $kill9 = false;

	public function __construct(array $commands = array()) {
	}

	public function writeln($line) {
		// TODO allow writers
		echo $line."\n";
	}

	public function run() {
		foreach ($this->commands as $c) {
			$cmd = $c['cmd'];
			$targetCount = $c['runCount'];
			$parseStr = $c['parseStr'];

			if (!is_numeric($targetCount)) {
				$this->writeln('  skipped...');
				continue;
			}

			$this->writeln('Handling running processes: '.$targetCount."\n";

			$pids = array();
			$process = new Process('ps -ef | grep "'.$parseStr.'" | grep -v grep | awk \'{print $2}\'');
			$process->setTimeout(5);
			$process->run();

			if (!$process->isSuccessful()) {
				throw new \Exception($process->getErrorOutput());
			}

			foreach (explode("\n", $process->getOutput()) as $line) {
				if (is_numeric($line)) {
					$pids[] = $line;
				}
			}

			$currentCount = count($pids);

			$this->writeln('  Current process count: '.$currentCount.', target count: '.$targetCount);

			// kill processes
			if ($currentCount > $targetCount) {
				$this->writeln('  Killing '.($currentCount-$targetCount).' processes');
				$killed = array();
				for ($i = 0, $currentCount; $currentCount > $targetCount; --$currentCount, ++$i) {
					$pid = $pids[$i];
					$process = new Process("kill ".($this->kill9 ? '-9' : '')." $pid");
					$process->setTimeout(5);
					$process->run();
					if (!$process->isSuccessful()) {
						throw new \Exception($process->getErrorOutput());
					}

					$killed[] = $pid;
				}

				$this->writeln("  Sent a ".($this->kill9 ? 'SIGKILL' : 'SIGTERM')." signal to the following PIDs:\n  ".implode(', ', $killed));

			// start processes
			} elseif ($currentCount < $targetCount) {
				$this->writeln('  Starting '.($targetCount-$currentCount).' processes ('.$cmd);

				for (; $currentCount < $targetCount; ++$currentCount) {
					// use exec() becuase from what I can tell, Process class can't
					// do a background job.
					exec(escapeshellcmd("$rootDir/$parseStr").' > /dev/null &');
					usleep(1000);
				}
			}
		}
	}
	
	public function addCommands($cmds) {
		if (!empty($cmds)) {
			foreach ($cmds as $cmd) {
				if (is_string($cmd)) {
					$this->addCommand($cmd);
				} else {
					$this->addCommand($cmd[0], $cmd[1]);
				}
			}
		}
	}

	public function addCommand($cmd, $runCount, $parseStr = null) {
		$this->commmands[$cmd] = array(
			'cmd' => $cmd,
			'parseStr' => $parseStr ?: $cmd,
			'runCount' => $runCount
		);
	}
}
