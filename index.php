<?php
session_start();

if (!isset($_SESSION['name'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: login.php');
}

if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['name']);
    header("location: index.php");
}

$successMessage = array();
$errorMessage = array();
$msg = "";


// Check if the form was submitted for adding a student
if (isset($_POST['studentName']) && isset($_POST['studentEmail']) && isset($_POST['totalAmount']) && isset($_POST['numSubjects'])) {
    // Retrieve form data for adding a student
    $studentName = $_POST['studentName'];
    $studentEmail = $_POST['studentEmail'];
    $totalAmount = $_POST['totalAmount'];
    $numSubjects = $_POST['numSubjects'];

    // Database connection
    $conn = mysqli_connect('localhost', 'root', '', 'tutorsquest');

    // Check the database connection
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Prepare and execute the SQL query to insert a new student
    $sql = "INSERT INTO Students (StudentName, StudentEmail, TotalAmount, AmountPaid, NumSubjects)
            VALUES ('$studentName', '$studentEmail', '$totalAmount', '0.00', '$numSubjects')";

    if (mysqli_query($conn, $sql)) {
        $studentId = mysqli_insert_id($conn); // Get the auto-generated StudentID

        // Insert records into the StudentSubjects table to associate subjects with the student
        for ($i = 1; $i <= $numSubjects; $i++) {
            $subjectKey = 'subject' . $i;

            // Check if the subject field exists in the $_POST array
            if (isset($_POST[$subjectKey])) {
                $subjectName = $_POST[$subjectKey];
                
                // Insert the subject into the Subjects table if it doesn't exist
                $insertSubjectSql = "INSERT IGNORE INTO Subjects (SubjectName) VALUES ('$subjectName')";
                mysqli_query($conn, $insertSubjectSql);
                
                // Retrieve the SubjectID for the inserted or existing subject
                $subjectIdQuery = "SELECT SubjectID FROM Subjects WHERE SubjectName = '$subjectName'";
                $subjectIdResult = mysqli_query($conn, $subjectIdQuery);
                $subjectId = -1; // Default value
                
                if ($subjectIdResult && mysqli_num_rows($subjectIdResult) > 0) {
                    $subjectRow = mysqli_fetch_assoc($subjectIdResult);
                    $subjectId = $subjectRow['SubjectID'];
                }
                
                // Associate the subject with the student in the StudentSubjects table
                $insertStudentSubjectSql = "INSERT INTO StudentSubjects (StudentID, SubjectID) VALUES ('$studentId', '$subjectId')";
                mysqli_query($conn, $insertStudentSubjectSql);
            }
        }
              array_push($successMessage ,"Student added successfully!");

        
    } else {
		     array_push( $errorMessage  , "Error adding student: " . mysqli_error($conn));

    }

    // Close the database connection
    mysqli_close($conn);
}

// Check if the form was submitted for adding a payment
if (isset($_POST['paymentStudentID']) && isset($_POST['paymentAmount']) && isset($_POST['paymentType'])) {
    // Retrieve form data for adding a payment
    $studentId = $_POST['paymentStudentID'];
    $paymentAmount = $_POST['paymentAmount'];
    $paymentType = $_POST['paymentType'];
    $msg = "hello";
    $paymentDate = date("Y-m-d"); // Get the current date
    $msg = "1";

    // Database connection (reuse the existing connection)
    $conn = mysqli_connect('localhost', 'root', '', 'tutorsquest');

    // Check the database connection
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Check if the student with the given ID exists
    $checkStudentSql = "SELECT * FROM Students WHERE StudentEmail = '$studentId'";
    $result = mysqli_query($conn, $checkStudentSql);
    $msg = "2";

    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            // The student with the provided ID exists

            // Prepare and execute the SQL query to insert a new payment
            $sql = "INSERT INTO Payments (StudentID, PaymentDate, PaymentAmount, PaymentType) VALUES ('$studentId', '$paymentDate', '$paymentAmount', '$paymentType')";
            $msg = "3";

            if (mysqli_query($conn, $sql)) {
                // Update the AmountPaid in the Students table
                $updateAmountPaidSql = "UPDATE Students SET AmountPaid = AmountPaid + '$paymentAmount' WHERE StudentEmail = '$studentId'";
                if (mysqli_query($conn, $updateAmountPaidSql)) {
					array_push($successMessage ,"Payment added successfully!");
                } else {
					array_push($errorMessage ,"Error updating student's AmountPaid: " );
                }
            } else {
				array_push($errorMessage ,"Error adding payment: " );
            }
        } else {
			array_push($errorMessage ,"Student with Email $studentId not found.");
        }
    } else {
			array_push($errorMessage ,"Error checking student: ");
    }
    // Close the database connection
    mysqli_close($conn);
}
?>





<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Amatic+SC">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: Arial, sans-serif;
			font-weight: bold;
            background: url("img/bg.jpg") no-repeat center center fixed;
            background-size: cover;
        }
        
        .tab-button {
            display: block;
            margin: 0 auto;
            background-color: #4CAF50;
            border: none;
            color: white;
            padding: 14px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .tab-button:hover {
            background-color: #45a049;
        }
        
        .tab-content {
            display: none;
        }
        
        table {
            border-collapse: collapse;
            width: 80%;
            margin: 20px auto;
        }
        
        th, td {
            text-align: left;
            padding: 8px;
        }
        
        tr {
            background-color: #f2f2f2;
        }
        
        th {
            background-color: #4CAF50;
            color: white;
        }
        
        input[type="text"], input[type="email"], input[type="number"] {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
    </style>
    <script>
        function openTab(tabName) {
            var i, tabContent;
            tabContent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabContent.length; i++) {
                tabContent[i].style.display = "none";
            }
            document.getElementById(tabName).style.display = "block";
        }

        function addSubjectFields() {
            var numSubjects = parseInt(document.getElementById('numSubjects').value);
            var subjectFieldsDiv = document.getElementById('subjectFields');
            subjectFieldsDiv.innerHTML = ''; // Clear previous fields

            for (var i = 1; i <= numSubjects; i++) {
                var subjectField = document.createElement('input');
                subjectField.type = 'text';
                subjectField.name = 'subject' + i;
                subjectField.placeholder = 'Subject ' + i;
                subjectField.required = true;

                // Append the subject input field to the subjectFieldsDiv
                subjectFieldsDiv.appendChild(subjectField);
                subjectFieldsDiv.appendChild(document.createElement('br'));
            }
        }
    </script>
</head>
<body id="page-top" data-spy="scroll" data-target=".navbar-fixed-top" class="bgimg">
    <div style="text-align: center;">
        <button class="tab-button" onclick="openTab('addPayment')">Add Payment</button>
        <button class="tab-button" onclick="openTab('addStudent')">Add Student</button>
        <button class="tab-button" onclick="openTab('viewStudents')">View Students</button>
		<button class="tab-button" onclick="openTab('uploadStudents')">Upload Students</button>
    </div>
	
	

    <!-- Rest of your HTML content here -->
	    <!-- Add Payment Tab -->
    <div id="addPayment" class="tab-content">
        <h2>Add Payment</h2>
        <form action="index.php" method="POST">
            <!-- Payment form fields here -->
            <label for="paymentStudentID">Student Email:</label>
            <input type="email" id="paymentStudentID" name="paymentStudentID" required><br>

            <label for="paymentAmount">Amount:</label>
            <input type="number" id="paymentAmount" name="paymentAmount" required><br>

            <label for="paymentType">Payment Type:</label>
            <select id="paymentType" name="paymentType" required>
                <option value="Zelle">Zelle</option>
                <option value="UPI">UPI</option>
                <option value="CompanyAccount">Company Account</option>
                <option value="Others">Others</option>
            </select><br>
            <input type="submit" value="Add Payment">
        </form>
    </div>

    <!-- Add Student Tab -->
    <div id="addStudent" class="tab-content">
        <h2>Add Student</h2>
        <form action="index.php" method="POST">
            <!-- Student form fields here -->
            <input type="text" name="studentName" placeholder="College Name" required><br>

            <input type="email" name="studentEmail" placeholder="Student Email" required><br>
            <input type="number" name="totalAmount" placeholder="Total Amount" required><br>
            <label>Number of Subjects:</label>
            <input type="number" name="numSubjects" id="numSubjects" min="1" max="10" required onchange="addSubjectFields()"><br>
            <div id="subjectFields">
            </div>

            <input type="submit" value="Add Student">
        </form>
    </div>
	
	    <!-- Upload Student Tab -->
 <div id="uploadStudents" class="tab-content">
    <h2>Upload Students</h2>
    <form action="upload.php" method="post" enctype="multipart/form-data">
        <input type="file" name="csvFile" accept=".csv" />
        <button type="submit">Import</button>
    </form>
</div>


    <!-- View Students Tab -->
    <div id="viewStudents" class="tab-content">
        <h2>View Students</h2>
        <?php
        // Database connection
        $conn = mysqli_connect('localhost', 'root', '', 'tutorsquest');

        // Check the database connection
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }

        // Query to fetch student details and payments
        $query = "SELECT s.StudentID, s.StudentEmail, GROUP_CONCAT(sub.SubjectName) AS Subjects, s.AmountPaid, s.TotalAmount, p.PaymentDate,
		p.PaymentAmount, p.PaymentType FROM Students AS s LEFT JOIN StudentSubjects AS 
		ss ON s.StudentID = ss.StudentID LEFT JOIN Subjects AS sub ON ss.SubjectID = sub.SubjectID LEFT JOIN Payments AS p ON s.StudentID = p.StudentID 
		GROUP BY s.StudentID";

        $result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) > 0) {
    echo '<table border="1">';
    echo '<tr><th>Student Email</th><th>Subjects</th><th>Amount Paid</th><th>Total Amount</th><th>Payments</th></tr>';
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td>' . $row['StudentEmail'] . '</td>';
        echo '<td>' . $row['Subjects'] . '</td>';
        echo '<td>' . $row['AmountPaid'] . '</td>';
        echo '<td>' . $row['TotalAmount'] . '</td>';
        echo '<td>';
              // Fetch payments for the current student
        $studentEmail = $row['StudentEmail'];
        $paymentQuery = "SELECT PaymentDate, PaymentAmount, PaymentType FROM Payments WHERE StudentID = '$studentEmail'";
        $paymentResult = mysqli_query($conn, $paymentQuery);
        
        if (mysqli_num_rows($paymentResult) > 0) {
            while ($paymentRow = mysqli_fetch_assoc($paymentResult)) {
                echo 'Date: ' . $paymentRow['PaymentDate'] . ', Amount: ' . $paymentRow['PaymentAmount'] . ', Type: ' . $paymentRow['PaymentType'] . '<br>';
            }
        } else {
            echo 'No payments found.';
        }
        
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo "No students found.";
}

        // Close the database connection
        mysqli_close($conn);
        ?>
    </div>

    <?php
    echo '<div style="color: red;">' . $msg . '</div>';
   if (!empty($errorMessage)) {
    echo '<div style="color: red;">';
    foreach ($errorMessage as $error) {
        echo $error . '<br>';
    }
    echo '</div>';
}

if (!empty($successMessage)) {
    echo '<div style="color: green;">';
    foreach ($successMessage as $success) {
        echo $success . '<br>';
    }
    echo '</div>';
}
    ?>
</body>
</html>
