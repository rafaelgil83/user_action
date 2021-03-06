<?php namespace App\Http\Controllers;

use App\Services\ActiveDirectory;
use App\Services\Mailer;
use App\Services\Reports;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Mail\Message;


class newHireController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | newHire Controller
    |--------------------------------------------------------------------------
    |
    | This controller renders your application's "dashboard" for users that
    | are authenticated. Of course, you are free to change or remove the
    | controller as you wish. It is just here to get your app started!
    |
    */

    /**
     * Create a new controller instance.
     *
     * @return \App\Http\Controllers\newHireController
     */

    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * Show the new hire form
     *
     * @return Response
     */
    public function index()
    {
        $user = User::current();

        return view('newHire', ['user' => $user, 'departments' => \Config::get('app.departments'),
            'companies' => \Config::get('app.companies'), 'hireStatus' => \Config::get('app.hireStatus'),
            'associate_class' => \Config::get('app.associate_class'), 'salaryType' => \Config::get('app.salaryType'),
            'locations' => \Config::get('app.locations'), 'payrollType' => \Config::get('app.payrollType'),
            'itDepartment' => \Config::get('app.itDepartment'),
            'applicationTeam' => \Config::get('app.applicationTeam'),
            'officeManager' => \Config::get('app.officeManager'), 'newDriver' => \Config::get('app.newDriver'),
            'finance' => \Config::get('app.finance'), 'payroll' => \Config::get('app.payroll')]);

    }


    /**
     * New request is created, create the report and show the welcome page
     *
     * @param Request $req
     *
     * @return \Illuminate\View\View
     */
    public function add(Request $req)
    {

        $name = trim($req->request->get('name'));
        $lastName = trim($req->request->get('lastName'));
        $fullName = $name . ' ' . $lastName;

        // generate newHire reports
        $newHireReport = \Config::get('app.newHireReportsPrefix') . $fullName . '.pdf';
        $newHireReport = Reports::escapeReportName($newHireReport);
        $view['newH'] = $req->request->all();
        $view['url'] = $req->url();

        Reports::generateReport($newHireReport, \Config::get('app.newHireReportsPath'), $req->request->get('reportType'), $view);


        //generate payroll Report
        $payrollReport = \Config::get('app.payrollNewHireReportsPrefix') . $fullName . '.pdf';
        $payrollReport = Reports::escapeReportName($payrollReport);
        Reports::generateReport($payrollReport, \Config::get('app.payrollNewHireReportsPath'), 'payroll', $view);


        //send the email
        $to = \Config::get('app.servicedesk');

        $mailNotifyDepartments = [];


        $mailNotifyDepartments[] = $req->request->get('company');
        if ($req->request->get('oManager') != '')
        {
            $mailNotifyDepartments[] = 'management';
        }
        if ($req->request->get('application') != '')
        {
            $mailNotifyDepartments[] = 'application';
        }
        if ($req->request->get('creditCard') != '')
        {
            $mailNotifyDepartments[] = 'creditCard';
        }
        if ($req->request->get('newDriver') != '')
        {
            $mailNotifyDepartments[] = 'newDriver';
        }
        if ($req->request->get('department') == 'Sales' && $req->request->get('company') == 'illy caffè North America, Inc')
        {
            $mailNotifyDepartments[] = 'sales';
        }


        $ccRecipients = MyMail::getRecipients('newHire', $mailNotifyDepartments, $req->request->get('managerEmail'));

        $subject = \Config::get('app.subjectPrefix') . $fullName;

        $attachment = \Config::get('app.newHireReportsPath') . $newHireReport;
        $attachment = isset($attachment) ? file_exists($attachment) ? $attachment : false : null;


        Mailer::send('emails.forms', [], function (Message $m) use ($to, $ccRecipients, $subject, $attachment)
        {
            $m->to($to, null)->subject($subject);
            $m->cc($ccRecipients);

            if ($attachment)
            {
                $m->attach($attachment);
            }
        });


        $ccRecipients[$to] = $to;
        $ccRecipients = array_unique(array_map("StrToLower", $ccRecipients));

        $samaacountname = strtolower(substr($lastName, 0, 5) . substr($name, 0, 2));

        //send request to engineering to create a mailbox and JDE access if needed
        Mailer::send('emails.joinGroups', ['userName' => $samaacountname, 'name' => $fullName,
            'manager' => $req->request->get('manager')], function (Message $m) use ($samaacountname)
        {
            $m->to(\Config::get('app.si_infra'), null)->subject('new user settings - ' . $samaacountname);

            // copy NA IT
            $cc[\Config::get('app.eMailITManager')] = \Config::get('app.eMailITManager');
            $cc[\Config::get('app.eMailIT')] = \Config::get('app.eMailIT');

            $m->cc($cc);
        });


        //add reminder for a week before new hire starts
        $dueDate = date('m/d/Y', strtotime('-1 week', strtotime($req->request->get('startDate'))));
        Schedule::addSchedule($dueDate, $samaacountname, $fullName, 'newHire_reminder', $req->request->get('startDate'), $req->request->get('application'), null);

        //create the username in the AD
        $ad = ActiveDirectory::get_connection();
        $ad->createUserAD($req);


        return view('thankYou', ['name' => $name, 'lastName' => $lastName, 'newHireReport' => $newHireReport,
            'reportType' => 'newhire', 'newHireRouteURL' => \Config::get('app.newHireURL'), 'sendMail' => $ccRecipients,
            'payrollNewHireReport' => $payrollReport, 'payrollNewHireRouteURL' => \Config::get('app.payrollNewHireURL'),
            'menu_Home' => '', 'menu_New' => '']);
    }

    public function checkEmail(Request $req)
    {
        $ad = ActiveDirectory::get_connection();
        $result = $ad->getEmail($req->request->get('email'));

        if (count($result) > 1)
        {
            return 'true';
        }
        else
        {
            return 'false';
        }
    }


}
