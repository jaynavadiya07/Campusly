<?php
/**
 * Campusly Timetable (timetable.php)
 */

$pageTitle = "Class Timetable - Campusly";
$activePage = "timetable.php";

require_once __DIR__ . '/auth.php';
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}
require_login(['student']);
require_once __DIR__ . '/includes/header.php';
?>

  <!-- Breadcrumbs -->
  <nav class="breadcrumbs-nav" aria-label="Breadcrumb">
    <ol class="breadcrumbs">
      <li><a href="index.php">Home</a></li>
      <li><a href="dashboard.php">Dashboard</a></li>
      <li aria-current="page">Timetable</li>
    </ol>
  </nav>

  <!-- Main Content Area -->
  <main id="main-content">
    <section class="panel" aria-labelledby="timetable-heading">
      <h1 id="timetable-heading">Weekly Class Timetable</h1>
      <p><strong>Charotar University of Science and Technology (CHARUSAT)</strong><br>
      Faculty of Technology and Engineering (FTE) | CSPIT<br>
      Department of Computer Science & Engineering (CSE) | B.Tech Semester - 3 (Odd Term) | Division - I</p>

      <div class="table-responsive" style="margin-top: 1.5rem; overflow-x: auto;">
        <table class="accessible-table" style="width: 100%; border-collapse: collapse;">
          <caption>CHARUSAT CSE Semester 3 Class Schedule (Monday to Friday)</caption>
          <thead>
            <tr>
              <th scope="col">Day / Time</th>
              <th scope="col">09:10 AM - 10:10 AM</th>
              <th scope="col">10:10 AM - 11:10 AM</th>
              <th scope="col" style="text-align: center;">11:10 AM - 12:10 PM</th>
              <th scope="col">12:10 PM - 01:10 PM</th>
              <th scope="col">01:10 PM - 02:10 PM</th>
              <th scope="col" style="text-align: center;">02:10 PM - 02:20 PM</th>
              <th scope="col">02:20 PM - 03:20 PM</th>
              <th scope="col">03:20 PM - 04:20 PM</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <th scope="row">Monday</th>
              <td>FDSA (T)<br><small>CSUC201 | Rm: 506 | VMP</small></td>
              <td>FCN (T)<br><small>ITUC201 | Rm: 506 | HYY</small></td>
              <td rowspan="5" style="vertical-align: middle; text-align: center; background: rgba(0, 0, 0, 0.02); font-weight: 600; color: var(--text-muted);">LUNCH<br>BREAK</td>
              <td colspan="2" style="text-align: center;">HS (T/P)<br><small>HSUV201 | Rm: 506 | ADK</small></td>
              <td rowspan="5" style="vertical-align: middle; text-align: center; background: rgba(0, 0, 0, 0.02); font-weight: 600; color: var(--text-muted);">SHORT<br>BREAK</td>
              <td colspan="2"><strong>Laboratory Sessions</strong><br>
                <small>A1: FDSA(P) [632 - AVK]<br>
                B1: FCN(P) [638 - BPP]<br>
                C1: WDF(P) [633 - ADK]</small>
              </td>
            </tr>
            <tr>
              <th scope="row">Tuesday</th>
              <td>MATHS (T)<br><small>MSUD203 | Rm: 506 | PM</small></td>
              <td>FDSA (T)<br><small>CSUC201 | Rm: 506 | ART</small></td>
              <td colspan="2"><strong>Laboratory Sessions</strong><br>
                <small>A1: FCN(P) [633 - BPP]<br>
                B1: OOP(P) [632 - ADK]<br>
                C1: FCN(P) [631 - HYY]</small>
              </td>
              <td>FCN (T)<br><small>ITUC201 | Rm: 506 | AN</small></td>
              <td>WDF (T)<br><small>ITUE204 | Rm: 506 | SRG</small></td>
            </tr>
            <tr>
              <th scope="row">Wednesday</th>
              <td>MATHS (T)<br><small>MSUD203 | Rm: 506 | PM</small></td>
              <td>OOP (T)<br><small>CEUE203 | Rm: 506 | SSS</small></td>
              <td colspan="2"><strong>Laboratory Sessions</strong><br>
                <small>A1: WDF(P) [633 - DSS]<br>
                B1: WDF(P) [632 - BPP]<br>
                C1: FDSA(P) [634 - AVK]</small>
              </td>
              <td>WDF (T)<br><small>ITUE204 | Rm: 506 | SRG</small></td>
              <td>FDSA (T)<br><small>CSUC201 | Rm: 506 | VMP</small></td>
            </tr>
            <tr>
              <th scope="row">Thursday</th>
              <td>WDF (T)<br><small>ITUE204 | Rm: 506 | VYP</small></td>
              <td>FCN (T)<br><small>ITUC201 | Rm: 506 | HYY</small></td>
              <td colspan="2"><strong>Laboratory Sessions</strong><br>
                <small>A1: WDF(P) [631 - VYP]<br>
                B1: FDSA(P) [638 - AVK]<br>
                C1: WDF(P) [632 - DSS]</small>
              </td>
              <td colspan="2"><strong>Laboratory Sessions</strong><br>
                <small>A1: OOP(P) [638 - ADK]<br>
                B1: WDF(P) [632 - BPP]<br>
                C1: OOP(P) [631 - SSS]</small>
              </td>
            </tr>
            <tr>
              <th scope="row">Friday</th>
              <td>MATHS (T)<br><small>MSUD203 | Rm: 506 | PM</small></td>
              <td>FDSA (T)<br><small>CSUC201 | Rm: 506 | ART</small></td>
              <td colspan="2"><strong>Counselling Sessions</strong><br>
                <small>A1 Batch [CS-631 - SRG]<br>
                B1 Batch [CS-632 - VYP]<br>
                C1 Batch [CS-633 - ADK]</small>
              </td>
              <td>FCN (T)<br><small>ITUC201 | Rm: 506 | HYY</small></td>
              <td>OOP (T)<br><small>CEUE203 | Rm: 506 | SSS</small></td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Course Directory Reference -->
    <section class="panel" aria-labelledby="courses-directory-heading">
      <h2 id="courses-directory-heading">Course & Faculty Directory</h2>
      <p>Course codes, titles, structure, and designated subject instructors reference list.</p>
      
      <div class="table-responsive">
        <table class="accessible-table" style="width: 100%; border-collapse: collapse;">
          <caption>Course details and faculty references</caption>
          <thead>
            <tr>
              <th scope="col">Course Code</th>
              <th scope="col">Course Name</th>
              <th scope="col">Faculty Abbr.</th>
              <th scope="col">Faculty Member Name</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td rowspan="4"><strong>CSUC201</strong></td>
              <td rowspan="4">FDSA (Fundamentals of Data Structure and Algorithm)</td>
              <td>ART</td>
              <td>Dr. Amit Thakkar</td>
            </tr>
            <tr>
              <td>AVK</td>
              <td>Prof. Avani Khokhariya</td>
            </tr>
            <tr>
              <td>GP</td>
              <td>Prof. Gayatree Parbat</td>
            </tr>
            <tr>
              <td>VMP</td>
              <td>Prof. Vidisha Pradhan</td>
            </tr>
            <tr>
              <td rowspan="5"><strong>ITUE204</strong></td>
              <td rowspan="5">WDF (Web Development Frameworks)</td>
              <td>SRG</td>
              <td>Prof. Srushti Gajjar</td>
            </tr>
            <tr>
              <td>VYP</td>
              <td>Prof. Vaibhavi Patel</td>
            </tr>
            <tr>
              <td>DSS</td>
              <td>Prof. Dhara Solanki</td>
            </tr>
            <tr>
              <td>BPP</td>
              <td>Prof. Brinda Patel</td>
            </tr>
            <tr>
              <td>ADK</td>
              <td>Prof. Akshita Kadam</td>
            </tr>
            <tr>
              <td rowspan="2"><strong>CEUE203</strong></td>
              <td rowspan="2">OOP (Object Oriented Programming)</td>
              <td>SSS</td>
              <td>Prof. Siddharth Shah</td>
            </tr>
            <tr>
              <td>ADK</td>
              <td>Prof. Akshita Kadam</td>
            </tr>
            <tr>
              <td rowspan="2"><strong>ITUC201</strong></td>
              <td rowspan="2">FCN (Fundamentals of Computer Networks)</td>
              <td>HYY</td>
              <td>Prof. Harshul Yagnik</td>
            </tr>
            <tr>
              <td>BPP</td>
              <td>Prof. Brinda Patel</td>
            </tr>
            <tr>
              <td><strong>MSUD203</strong></td>
              <td>MATHS (Discrete Mathematics)</td>
              <td>PM</td>
              <td>Prof. Pragnesh Makwana</td>
            </tr>
            <tr>
              <td><strong>HSUV201</strong></td>
              <td>HS (Creativity, Problem Solving and Innovation)</td>
              <td>ADK</td>
              <td>Prof. Akshita Kadam</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <div class="next-step-panel">
      <div class="next-step-content">
        <h3>Need Course Study Materials?</h3>
        <p>Download lecture reference notes, presentations, and lab syllabus manuals.</p>
      </div>
      <div>
        <a href="material.php" class="btn btn-primary">Access Materials</a>
      </div>
    </div>
  </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
