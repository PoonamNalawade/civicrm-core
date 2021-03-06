<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

require_once 'CiviTest/CiviUnitTestCase.php';


/**
 *  Test APIv3 civicrm_report_instance_* functions
 *
 *  @package CiviCRM_APIv3
 *  @subpackage API_Report
 */

class api_v3_ReportTemplateTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  public $_eNoticeCompliant = TRUE;
  function setUp() {
    parent::setUp();
    $this->_sethtmlGlobals();
  }

  function tearDown() {}

  public function testReportTemplate() {
    $result = $this->callAPISuccess('ReportTemplate', 'create', array(
      'label' => 'Example Form',
      'description' => 'Longish description of the example form',
      'class_name' => 'CRM_Report_Form_Examplez',
      'report_url' => 'example/path',
      'component' => 'CiviCase',
    ));
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $entityId = $result['id'];
    $this->assertTrue(is_numeric($entityId), 'In line ' . __LINE__);
    $this->assertEquals(7, $result['values'][$entityId]['component_id'], 'In line ' . __LINE__);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      AND option_group_id = 41 ');
    $this->assertDBQuery(1, 'SELECT is_active FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"');

    // change component to null
    $result = $this->callAPISuccess('ReportTemplate', 'create', array(      'id' => $entityId,
      'component' => '',
    ));
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      AND option_group_id = 41');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      AND component_id IS NULL');

    // deactivate
    $result = $this->callAPISuccess('ReportTemplate', 'create', array(      'id' => $entityId,
      'is_active' => 0,
    ));
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      AND option_group_id = 41');
    $this->assertDBQuery(0, 'SELECT is_active FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"');

    // activate
    $result = $this->callAPISuccess('ReportTemplate', 'create', array(      'id' => $entityId,
      'is_active' => 1,
    ));
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      AND option_group_id = 41');
    $this->assertDBQuery(1, 'SELECT is_active FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"');

    $result = $this->callAPISuccess('ReportTemplate', 'delete', array(      'id' => $entityId,
    ));
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      ');
  }

  /**
   *
   */
  function testReportTemplateGetRowsContactSummary() {
    $result = $this->callAPISuccess('report_template', 'getrows', array(
      'report_id' => 'contact/summary',
    ));

    //the second part of this test has been commented out because it relied on the db being reset to
    // it's base state
    //wasn't able to get that to work consistently
    // however, when the db is in the base state the tests do pass
    // and because the test covers 'all' contacts we can't create our own & assume the others don't exist
    /*
    $this->assertEquals(2, $result['count']);
    $this->assertEquals('Default Organization', $result[0]['civicrm_contact_sort_name']);
    $this->assertEquals('Second Domain', $result[1]['civicrm_contact_sort_name']);
    $this->assertEquals('15 Main St', $result[1]['civicrm_address_street_address']);
    */
  }

  /**
   * @dataProvider getReportTemplates
   */
  function testReportTemplateGetRowsAllReports($reportID) {
    if(stristr($reportID, 'has existing issues')) {
      $this->markTestIncomplete($reportID);
    }
     $result = $this->callAPISuccess('report_template', 'getrows', array(
       'report_id' => $reportID,
    ));
  }

  /**
   * @dataProvider getReportTemplates
   */
  function testReportTemplateGetStatisticsAllReports($reportID) {
    if(stristr($reportID, 'has existing issues')) {
      $this->markTestIncomplete($reportID);
    }
    if(in_array($reportID , array('contribute/softcredit', 'contribute/bookkeeping'))) {
      $this->markTestIncomplete($reportID . " has non enotices when calling statistics fn");
    }
    $result = $this->callAPISuccess('report_template', 'getstatistics', array(
      'report_id' => $reportID,
    ));
  }

  /**
   * Data provider function for getting all templates, note that the function needs to
   * be static so cannot use $this->callAPISuccess
   */
  public static function getReportTemplates() {
    $reportsToSkip = array(
        'activity' =>  'does not respect function signature on from clause',
        'walklist' => 'Notice: Undefined index: type in CRM_Report_Form_Walklist_Walklist line 155.
                       (suspect the select function should be removed in favour of the parent (state province field)
                      also, type should be added to state province & others? & potentially getAddressColumns fn should be
                      used per other reports',
        'contribute/repeat' => 'Reports with important functionality in postProcess are not callable via the api. For variable setting recommend beginPostProcessCommon, for temp table creation recommend From fn',
        'contribute/organizationSummary' => 'Failure in api call for report_template getrows:  Only variables should be assigned by reference line 381',
        'contribute/householdSummary' => '(see contribute/repeat) Undefined property: CRM_Report_Form_Contribute_HouseholdSummary::$householdContact LINE 260, property should be declared on class, for api accessibility should be set in beginPreProcess common',
        'contribute/topDonor' => 'construction of query in postprocess makes inaccessible ',
        'contribute/sybunt' => 'e notice - (ui gives fatal error at civicrm/report/contribute/sybunt&reset=1&force=1
                                e-notice is on yid_valueContribute/Sybunt.php(214) because at the force url "yid_relative" not "yid_value" is defined',
        'contribute/lybunt' => 'same as sybunt - fatals on force url & test identifies why',
        'event/income' => 'I do no understant why but error is Call to undefined method CRM_Report_Form_Event_Income::from() in CRM/Report/Form.php on line 2120',
        'contact/relationship' => '(see contribute/repeat), property declaration issue, Undefined property: CRM_Report_Form_Contact_Relationship::$relationType in /Contact/Relationship.php(486):',
        'case/demographics' => 'Undefined index: operatorType Case/Demographics.php(319)',
        'activitySummary' => 'Undefined index: group_bys_freq m/ActivitySummary.php(191)',
        'event/incomesummary' => 'Undefined index: title, Report/Form/Event/IncomeCountSummary.php(187)',
        'logging/contact/summary' => '(likely to be test releated) probably logging off Undefined index: Form/Contact/LoggingSummary.php(231): PHP',
        'logging/contact/detail' => '(likely to be test releated) probably logging off  DB Error: no such table',
        'logging/contribute/summary' => '(likely to be test releated) probably logging off DB Error: no such table',
        'logging/contribute/detail' => '(likely to be test releated) probably logging off DB Error: no such table',
        'survey/detail' => '(likely to be test releated)  Undefined index: CiviCampaign civicrm CRM/Core/Component.php(196)',
        'contribute/history' => 'Declaration of CRM_Report_Form_Contribute_History::buildRows() should be compatible with CRM_Report_Form::buildRows($sql, &$rows)',
    );

    $reports = civicrm_api3('report_template', 'get', array('return' => 'value', 'options' => array('limit' => 500)));
    foreach ($reports['values'] as $report) {
      if(empty($reportsToSkip[$report['value']])) {
        $reportTemplates[] = array($report['value']);
      }
      else {
        $reportTemplates[] = array($report['value']. " has existing issues :  " . $reportsToSkip[$report['value']]);
      }
    }



    return $reportTemplates;
  }
}
