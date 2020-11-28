<?php

namespace App\Http\Controllers;

use App\Invoice;
use App\InvoiceItems;
use App\InvoiceSetting;
use App\OfflinePaymentMethod;
use App\PaymentGatewayCredentials;
use App\Project;
use App\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Helper\Reply;
use App\Http\Requests\Lead\StoreRequest;
use App\Lead;
use App\LeadCustomForm;
use App\LeadStatus;
use App\PusherSetting;
use App\Setting;
use App\TaskboardColumn;
use App\TaskFile;
use App\User;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('home');
    }

    public function login()
    {
        return redirect(route('login'));
    }

    public function invoice($id)
    {
        $this->pageTitle = 'app.menu.invoices';
        $this->pageIcon = 'icon-money';

        $this->invoice = Invoice::with('currency', 'project', 'project.client')->whereRaw('md5(id) = ?', $id)->firstOrFail();
        $this->paidAmount = $this->invoice->getPaidAmount();

        if ($this->invoice->discount > 0) {
            if ($this->invoice->discount_type == 'percent') {
                $this->discount = (($this->invoice->discount / 100) * $this->invoice->sub_total);
            } else {
                $this->discount = $this->invoice->discount;
            }
        } else {
            $this->discount = 0;
        }

        $taxList = array();

        $items = InvoiceItems::whereNotNull('taxes')
            ->where('invoice_id', $this->invoice->id)
            ->get();

        foreach ($items as $item) {
            foreach (json_decode($item->taxes) as $tax) {
                $this->tax = InvoiceItems::taxbyid($tax)->first();
                if ($this->tax) {
                    if (!isset($taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'])) {
                        $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = ($this->tax->rate_percent / 100) * $item->amount;
                    } else {
                        $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + (($this->tax->rate_percent / 100) * $item->amount);
                    }
                }
            }
        }

        $this->taxes = $taxList;

        $this->settings = Setting::organisationSetting();
        $this->credentials = PaymentGatewayCredentials::first();
        $this->methods = OfflinePaymentMethod::activeMethod();
        $this->invoiceSetting = InvoiceSetting::first();

        return view('invoice', [
            'companyName' => $this->settings->company_name,
            'pageTitle' => $this->pageTitle,
            'pageIcon' => $this->pageIcon,
            'global' => $this->settings,
            'setting' => $this->settings,
            'settings' => $this->settings,
            'invoice' => $this->invoice,
            'paidAmount' => $this->paidAmount,
            'discount' => $this->discount,
            'credentials' => $this->credentials,
            'taxes' => $this->taxes,
            'methods' => $this->methods,
            'invoiceSetting' => $this->invoiceSetting,
        ]);
    }
    public function app()
    {
        $setting = Setting::select('id', 'company_name')->first();

        return ['data' => $setting];
    }

    public function gantt($ganttProjectId)
    {
        $this->settings = cache()->remember(
            'global-setting',
            60 * 60 * 24,
            function () {
                return \App\Setting::first();
            }
        );
        $this->ganttProjectId = $ganttProjectId;
        $this->project = Project::whereRaw('md5(id) = ?', $ganttProjectId)->first();

        return view('gantt', [
            'ganttProjectId' => $this->ganttProjectId,
            'global' => $this->settings,
            'project' => $this->project
        ]);
    }

    public function ganttData($ganttProjectId)
    {

        $data = array();
        $links = array();

        $projects = Project::select('id', 'project_name', 'start_date', 'deadline', 'completion_percent')
            ->whereRaw('md5(id) = ?', $ganttProjectId)
            ->get();

        $id = 0; //count for gantt ids
        foreach ($projects as $project) {
            $id = $id + 1;
            $projectId = $id;

            // TODO::ProjectDeadline to do
            $projectDuration = 0;
            if ($project->deadline) {
                $projectDuration = $project->deadline->diffInDays($project->start_date);
            }

            $data[] = [
                'id' => $projectId,
                'text' => ucwords($project->project_name),
                'start_date' => $project->start_date->format('Y-m-d H:i:s'),
                'duration' => $projectDuration,
                'progress' => $project->completion_percent / 100,
                'color' => 'grey',
                'textColor' => 'white',
                'project_id' => $project->id
            ];

            $tasks = Task::projectTasks($project->id, null, "0");

            foreach ($tasks as $key => $task) {
                $id = $id + 1;

                $taskDuration = $task->due_date->diffInDays($task->start_date);
                $taskDuration = $taskDuration + 1;

                $color = $task->board_column->label_color;

                $data[] = [
                    'id' => $task->id,
                    'text' => ucfirst($task->heading),
                    'start_date' => (!is_null($task->start_date)) ? $task->start_date->format('Y-m-d') : $task->due_date->format('Y-m-d'),
                    'duration' => $taskDuration,
                    'parent' => $projectId,
                    'color' => $color,
                    'taskid' => $task->id
                ];

                $links[] = [
                    'id' => $id,
                    'source' => $task->dependent_task_id != '' ? $task->dependent_task_id : $projectId,
                    'target' => $task->id,
                    'type' => $task->dependent_task_id != '' ? 0 : 1
                ];
            }
        }

        $ganttData = [
            'data' => $data,
            'links' => $links
        ];

        return response()->json($ganttData);
    }


    public function taskDetail($id)
    {
        $this->settings = cache()->remember(
            'global-setting',
            60 * 60 * 24,
            function () {
                return \App\Setting::first();
            }
        );
        $this->task = Task::with('board_column', 'subtasks', 'project', 'users', 'files')->findOrFail($id);
        $view = view('task_detail', [
            'task' => $this->task,
            'global' => $this->settings,
        ])->render();
        return Reply::dataOnly(['status' => 'success', 'view' => $view]);
    }

    public function taskFiles($id)
    {
        $this->taskFiles = TaskFile::where('task_id', $id)->get();
        return view('task-files', ['taskFiles' => $this->taskFiles]);
    }

    public function history($id)
    {
        $this->settings = cache()->remember(
            'global-setting',
            60 * 60 * 24,
            function () {
                return \App\Setting::first();
            }
        );
        $this->task = Task::with('board_column', 'history', 'history.board_column')->findOrFail($id);
        $view = view('admin.tasks.history', [
            'task' => $this->task,
            'global' => $this->settings,
        ])->render();
        return Reply::dataOnly(['status' => 'success', 'view' => $view]);
    }

    public function taskboard(Request $request, $encrypt)
    {
        $this->global = cache()->remember(
            'global-setting',
            60 * 60 * 24,
            function () {
                return \App\Setting::first();
            }
        );

        try {
            $companyName = decrypt($encrypt);
            if ($companyName != $this->global->company_name) {
                abort(404);
            }
        } catch (DecryptException $e) {
            abort(404);
        }

        $this->pusherSettings = PusherSetting::first();
        $this->startDate = Carbon::today()->subDays(15)->format($this->global->date_format);
        $this->endDate = Carbon::today()->addDays(15)->format($this->global->date_format);
        $this->projects = Project::allProjects();
        $this->clients = User::allClients();
        $this->employees = User::allEmployees();


        return view('taskboard', [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'projects' => $this->projects,
            'clients' => $this->clients,
            'employees' => $this->employees,
            'global' => $this->global,
            'pusherSettings' => $this->pusherSettings,
        ]);
    }

    public function taskBoardData(Request $request)
    {
        $this->global = cache()->remember(
            'global-setting',
            60 * 60 * 24,
            function () {
                return \App\Setting::first();
            }
        );
        if (request()->ajax()) {

            $startDate = Carbon::createFromFormat($this->global->date_format, $request->startDate)->toDateString();
            $endDate = Carbon::createFromFormat($this->global->date_format, $request->endDate)->toDateString();

            $boardColumns = TaskboardColumn::with(['tasks' => function ($q) use ($startDate, $endDate, $request) {
                $q->with(['subtasks', 'completedSubtasks', 'comments', 'users', 'project'])
                    ->leftJoin('projects', 'projects.id', '=', 'tasks.project_id')
                    ->leftJoin('users as client', 'client.id', '=', 'projects.client_id')
                    ->join('task_users', 'task_users.task_id', '=', 'tasks.id')
                    ->join('users', 'task_users.user_id', '=', 'users.id')
                    ->join('taskboard_columns', 'taskboard_columns.id', '=', 'tasks.board_column_id')
                    ->leftJoin('users as creator_user', 'creator_user.id', '=', 'tasks.created_by')
                    ->select('tasks.*')
                    ->groupBy('tasks.id');

                $q->where(function ($task) use ($startDate, $endDate) {
                    $task->whereBetween(DB::raw('DATE(tasks.`due_date`)'), [$startDate, $endDate]);

                    $task->orWhereBetween(DB::raw('DATE(tasks.`start_date`)'), [$startDate, $endDate]);
                });
                $q->whereNull('projects.deleted_at');

                if ($request->projectID != 0 && $request->projectID !=  null && $request->projectID !=  'all') {
                    $q->where('tasks.project_id', '=', $request->projectID);
                }

                if ($request->clientID != '' && $request->clientID !=  null && $request->clientID !=  'all') {
                    $q->where('projects.client_id', '=', $request->clientID);
                }

                if ($request->assignedTo != '' && $request->assignedTo !=  null && $request->assignedTo !=  'all') {
                    $q->where('task_users.user_id', '=', $request->assignedTo);
                }

                if ($request->assignedBY != '' && $request->assignedBY !=  null && $request->assignedBY !=  'all') {
                    $q->where('creator_user.id', '=', $request->assignedBY);
                }

                $q->where('tasks.is_private', '=', 0);
            }])->orderBy('priority', 'asc')->get();

            $this->boardColumns = $boardColumns;

            $this->startDate = $startDate;
            $this->endDate = $endDate;

            $view = view('taskboard_board_data', [
                'boardColumns' => $this->boardColumns,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'global' => $this->global,
            ])->render();
            return Reply::dataOnly(['view' => $view]);
        }
    }

    public function taskShare($id)
    {
        $this->pageTitle = 'app.task';

        $this->settings = cache()->remember(
            'global-setting',
            60 * 60 * 24,
            function () {
                return \App\Setting::first();
            }
        );
        $this->task = Task::with('board_column', 'subtasks', 'project', 'users')->whereRaw('md5(id) = ?', $id)->firstOrFail();

        return view('task-share', [
            'task' => $this->task,
            'global' => $this->settings
        ]);
    }

    /**
     * custom lead form
     *
     * @return \Illuminate\Http\Response
     */
    public function leadForm()
    {
        $this->pageTitle = 'modules.lead.leadForm';
        $this->settings = cache()->remember(
            'global-setting',
            60 * 60 * 24,
            function () {
                return \App\Setting::first();
            }
        );
        $this->leadFormFields = LeadCustomForm::where('status', 'active')
            ->orderBy('field_order', 'asc')->get();

        return view('lead-form', [
            'pageTitle' => $this->pageTitle,
            'leadFormFields' => $this->leadFormFields,
            'global' => $this->settings
        ]);
    }

    /**
     * save lead
     *
     * @return \Illuminate\Http\Response
     */
    public function leadStore(StoreRequest $request)
    {
        $leadStatus = LeadStatus::where('default', '1')->first();
        $settings = cache()->remember(
            'global-setting',
            60 * 60 * 24,
            function () {
                return \App\Setting::first();
            }
        );

        $lead = new Lead();
        $lead->company_name = (request()->has('company_name') ? $request->company_name : '');
        $lead->website = (request()->has('website') ? $request->website : '');
        $lead->address = (request()->has('address') ? $request->address : '');
        $lead->client_name = (request()->has('client_name') ? $request->client_name : '');
        $lead->client_email = (request()->has('client_email') ? $request->client_email : '');
        $lead->mobile = (request()->has('mobile') ? $request->mobile : '');
        $lead->status_id = $leadStatus->id;
        $lead->value = 0;
        $lead->currency_id = $settings->currency->id;
        $lead->save();

        return Reply::success(__('messages.LeadAddedUpdated'));
    }
}
