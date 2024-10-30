<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'nullable|integer',
        ]);

        if ($user_id = $validatedData['user_id'] ?? null) {
            $response = $this->repository->getUsersJobs($user_id);
        } elseif ($request->__authenticatedUser->user_type == config('app.users.admin_id') || $request->__authenticatedUser->user_type == config('users.super_admin_role')) {
            $response = $this->repository->getAll($request);
        } else {
            \Log::warning('Unauthorized access attempt by user ID: ' . $request->__authenticatedUser->id);
            return response()->json(['error' => 'Unauthorized access'], 401);
        }

        return response()->json($response, 200);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->find($id);
        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        return response()->json($job, 200);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'from_language_id' => 'required|integer',
            'immediate' => 'required|in:yes,no',
            'due_date' => 'nullable|date_format:m/d/Y',
            'due_time' => 'nullable|date_format:H:i',
            'customer_phone_type' => 'nullable|string',
            'customer_physical_type' => 'nullable|string',
            'duration' => 'required|string',
            'job_for' => 'required|array',
            'job_for.*' => 'string|in:normal,certified,certified_in_law,certified_in_health',
        ]);

        $response = $this->repository->store($request->__authenticatedUser, $validatedData);
        return response()->json($response, $response['status'] === 'success' ? 200 : 400);
    }


    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        // Validate the request data
        $request->validate([
            'due' => 'required|date',
            'from_language_id' => 'required|integer',
            'admin_comments' => 'nullable|string',
            'reference' => 'nullable|string',
        ]);

        $data = $request->except(['_token', 'submit']);
        $cuser = $request->__authenticatedUser;
        $response = $this->repository->updateJob($id, $data, $cuser);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        $adminSenderEmail = config('app.adminemail');
        $data = $request->all();

        $response = $this->repository->storeJobEmail($data);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        if($user_id = $request->get('user_id')) {

            $response = $this->repository->getUsersJobsHistory($user_id, $request);
            return response($response);
        }

        return null;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJob($data, $user);

        return response($response);
    }

    public function acceptJobWithId(Request $request)
    {
        $data = $request->get('job_id');
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJobWithId($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->cancelJobAjax($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->endJob($data);

        return response($response);

    }

    public function customerNotCall(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->customerNotCall($data);

        return response($response);

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->getPotentialJobs($user);

        return response($response);
    }

    public function distanceFeed(Request $request)
    {
        $data = $request->all();
        $distance = $data['distance'] ?? '';
        $time = $data['time'] ?? '';
        $jobid = $data['jobid'] ?? null;
        $session = $data['session_time'] ?? '';
        $flagged = ($data['flagged'] ?? 'false') === 'true' ? 'yes' : 'no';
        $manually_handled = ($data['manually_handled'] ?? 'false') === 'true' ? 'yes' : 'no';
        $by_admin = ($data['by_admin'] ?? 'false') === 'true' ? 'yes' : 'no';
        $admincomment = $data['admincomment'] ?? '';

        if (is_null($jobid)) {
            return response()->json(['status' => 'fail', 'message' => 'Job ID is required'], 400);
        }

        if ($flagged === 'yes' && empty($adminComment)) {
            return response()->json(['status' => 'fail', 'message' => 'Please, add a comment for flagged jobs'], 400);
        }

        if ($time || $distance) {

            Distance::where('job_id', '=', $jobid)->update(array('distance' => $distance, 'time' => $time));
        }

        if ($admincomment || $session || $flagged || $manually_handled || $by_admin) {

            Job::where('id', '=', $jobid)->update(array('admin_comments' => $admincomment, 'flagged' => $flagged, 'session_time' => $session, 'manually_handled' => $manually_handled, 'by_admin' => $by_admin));
        }

        return response('Record updated!');
    }

    public function reopen(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->reopen($data);

        return response($response);
    }

    public function resendNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }
        $job_data = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $job_data, '*');

        return response(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }
        $job_data = $this->repository->jobToData($job);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

}
