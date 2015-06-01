<?php

/**
 * Core class for background jobs
 *
 * Date: 23.11.12
 * Time: 17:03
 * @version 1.2
 * @author goshi
 * @package web-T[CORE]
 *
 * Changelog:
 *  1.2     10.05.13/goshi  add initialiation for admin functions/templates in 'run' method
 *  1.1     10.03.13/goshi  adding event on done, simply code refactor,
 *	1.0		23.11.12/goshi	...
 */

namespace webtFramework\Core;

use webtFramework\Components\Event\oEvent;

/**
 * @package web-T[CORE]
 */
class webtJobs{

    /**
     * @var null|oPortal
     */
    protected $_p = null;

	public function __construct(oPortal &$p){
		$this->_p = $p;
	}

    /**
     * get job from queue by its id
     * @param bool $job_id
     * @return array|null|void|\webtFramework\Interfaces\oModel
     */
    public function get($job_id = false){

        $conditions = array('where' => array('status' => JOB_PENDING), 'limit' => 1);

		if ($job_id){

            $conditions['where']['id'] = $job_id;

		} else {
            $conditions['order'] = array('date_start' => 'asc', 'priority' => 'desc');

		}

		return $this->_p->db->getManager()->getRepository('CoreJob')->findOneBy($conditions);

	}

    /**
     * remove job from queue by its id
     * @param bool $job_id
     * @return array|bool|null
     */
    public function remove($job_id = false){

		$result = false;
		if ($job_id){

			$result = $this->_p->db->query($this->_p->db->getQueryBuilder()->compileDelete($this->_p->Model('CoreJob'), array('where' => array('[PRIMARY]' => $job_id))));
		}

		return $result;

	}

    /**
     * run queue
     * @param bool $is_debug
     */
    public function runQueue($is_debug = false){

        if ($is_debug){
            $this->_p->response->send("* Starting jobs queue...");
        }

        while ($job = $this->get()){

            if ($is_debug){
                $this->_p->response->send("* Start job: ".$job->getPrimaryValue());
            }
            $this->run($job);
            if ($is_debug){
                $this->_p->response->send("* End job: ".$job->getPrimaryValue());
            }

        }

        if ($is_debug){
            $this->_p->response->send("* End jobs queue.");
        }


    }

    /**
     * run selected job
     * @param null|\webtFramework\Models\CoreJob $job
     * @return bool|string
     * @throws \Exception
     */
    public function run($job = null){

		$result = false;
		if ($job){

            if (is_numeric($job))
                $job = $this->get($job);

            if (!$job)
                return $result;

            $repo = $this->_p->db->getManager()->getRepository('CoreJob');

            $repo->update($job, array(
                'status' => JOB_PROCESS,
                'date_start_job' => $this->_p->getTime()
            ));

			// starting work
			$stored_exc = null;

			ob_start();
			try {
				$p = $portal = &$this->_p;
				eval($job->getJob().';');

			} catch (\Exception $e) {
				$stored_exc = $e;
				// Handle an error
			}
			$result = ob_get_contents();

			ob_end_clean();

            // ending work
            $repo->update($job, array(
                'status' => JOB_DONE,
                'date_end_job' => time()
            ));


            $this->_p->events->dispatch(new oEvent(
                WEBT_CORE_JOB_DONE,
                $this,
                array('job' => $job, 'description' => $stored_exc ? $stored_exc->getMessage() : ''))
            );

            unset($job);

			// throwing saved exception
			if ($stored_exc) {
				throw new \Exception($stored_exc);
			}

		}

		return $result;

	}


    /**
     * add job to the queue
     * @param string $job command string for runnning
     * @param null $timestamp unixtime for running
     * @param int $priority priority level
     * @param int $event event level
     * @param bool $is_send_email_on_done flag for send email
     * @param bool $is_forced_background flag for send job to background forced
     * @return array|bool|null|void
     */
    public function add($job, $timestamp = null, $priority = JOB_PRIOR_LOW, $event = null, $is_send_email_on_done = false, $is_forced_background = false){

		if (!$job)
			return false;

		if (!$timestamp)
			$timestamp = $this->_p->getTime();

        $em = $this->_p->db->getManager();
        $model = $this->_p->Model('CoreJob');
        $model->setModelData(array(
            'job' => $job,
            'date_start' => $timestamp,
            'event_type' => $event,
            'is_send_email_on_done' => $is_send_email_on_done,
            'status' => JOB_PENDING,
            'priority' => $priority,
            'is_forced_background' => $is_forced_background
        ));

		$job_id = $em->initPrimaryValue($model);

        $em->save($model);

		// if forcing calling jobs (see common.php section 'jobs_mode')
		if ($this->_p->getVar('jobs')['mode'] == 'normal' && !$is_forced_background){

			$this->run($job_id);
			$this->remove($job_id);

		}

		return $job_id;
	}


}

