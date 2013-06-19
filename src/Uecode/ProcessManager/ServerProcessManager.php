<?php
/**
 * @author John Pancoast <shideon@gmail.com>
 */

namespace Uecode\ProcessManager;

use Symfony\Component\Process\Process;

class ServerProcessManager
{
	/**
	 * @var array commands we'll run
	 *
	 * @access private
	 */
	private $commands = array();

	/**
	 * Adjusts counts of running processes for each command in self::$commands
	 *
	 * @access public
	 */
	public function run($kill9 = false) {
		foreach ($this->commands as $c) {
			$cmd = $c['cmd'];
			$targetCount = $c['runCount'];
			$parseStr = $c['parseStr'];

			if (!is_numeric($targetCount)) {
				$this->writeln('  skipped...');
				continue;
			}

			$this->writeln("Handling running processes: '$cmd'");

			$pids = $this->getProcesses($parseStr);

			$currentCount = count($pids);

			$this->writeln('  Current process count: '.$currentCount.', target count: '.$targetCount);

			// kill processes
			if ($currentCount > $targetCount) {
				$this->writeln('  Killing '.($currentCount-$targetCount).' process(es)');
				$killed = array();
				for ($i = 0, $currentCount; $currentCount > $targetCount; --$currentCount, ++$i) {
					$pid = $pids[$i];
					$process = new Process("kill ".($kill9 ? '-9' : '')." $pid");
					$process->setTimeout(5);
					$process->run();
					if (!$process->isSuccessful()) {
						throw new \Exception($process->getErrorOutput());
					}

					$killed[] = $pid;
				}

				$this->writeln("  Sent a ".($kill9 ? 'SIGKILL' : 'SIGTERM')." signal to the following PIDs:\n  ".implode(', ', $killed));

			// start processes
			} elseif ($currentCount < $targetCount) {
				$this->writeln('  Starting '.($targetCount-$currentCount).' process(es) ');

				for (; $currentCount < $targetCount; ++$currentCount) {
					// use exec() becuase from what I can tell, Process class can't
					// do a background job.
					exec(escapeshellcmd("$cmd").' > /dev/null &');
					usleep(1000);
				}
			}
		}
	}
	
	/**
	 * Write a line of output
	 *
	 * @param string $line
	 * @access public
	 */
	public function writeln($line) {
		// TODO allow writers
		echo $line."\n";
	}

	/**
	 * Add commands to start or stop based on their counts.
	 *
	 * Array should be in the form:
	 * array(
	 *   array(
	 *     <cmd>
	 *     <runcount>
	 *     <parse string> [OPTIONAL]
	 *   )
	 * )
	 *
	 * @access public
	 * @param array $cmds
	 */
	public function addCommands(array $cmds = array()) {
		if (!empty($cmds)) {
			foreach ($cmds as $cmd) {
				$this->addCommand($cmd[0], $cmd[1], isset($cmd[2]) ? $cmd[2] : null);
			}
		}
	}

	/**
	 * Add command to start of stop based on passed run count.
	 *
	 * @access public
	 * @param string $cmd The command to run.
	 * @param int $runCount The amount this command/process should be running.
	 * @
	 */
	public function addCommand($cmd, $runCount, $parseStr = null) {
		$this->commands[] = array(
			'cmd' => $cmd,
			'parseStr' => $parseStr ?: $cmd,
			'runCount' => $runCount
		);
	}

	/**
	 * Given a command string to parse in ps output, return the processes
	 *
	 * @access public
	 * @param string $commandParseStr The string to look for in ps
	 * @return array
	 */
	public function getProcesses($commandParseStr) {
		$pids = array();
		$process = new Process('ps -ef | grep "'.$commandParseStr.'" | grep -v grep | awk \'{print $2}\'');
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

		return $pids;
	}

	/**
	 * Given a command string to parse in ps output, return the amount it's running
	 *
	 * @access public
	 * @param string $commandParseStr The string to look for in ps
	 * @return int
	 */
	public function getProcessRunCount($commandParseStr) {
		return count($this->getProcesses($commandParseStr));
	}

	/**
	 * Gets run count for each command
	 *
	 * @access public
	 * @return array
	 */
	public function getProcessRunCounts() {
		$ret = array();
		foreach ($this->commands as $c) {
			$ret[$c['cmd']] = $this->getProcessRunCount($c['parseStr']);
		}
		return $ret;
	}

	/**
	 * Prints run count for each command
	 *
	 * @access public
	 */
	public function printProcessRunCounts() {
		foreach ($this->getProcessRunCounts() as $cmd => $count) {
			$this->writeln($count." ".$cmd);
		}
	}
}
