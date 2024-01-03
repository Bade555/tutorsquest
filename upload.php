<?php

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Your database connection parameters
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'tutorsquest';

    // Create a MySQLi connection
    $conn = mysqli_connect($host, $username, $password, $database);

    // Check connection
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Check if a file was uploaded
    if (isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] == UPLOAD_ERR_OK) {
        // Path to store the uploaded file
        $uploadPath = 'uploads/';

        // Ensure the uploads directory exists
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        // Generate a unique filename
        $fileName = uniqid('csv_') . '.csv';

        // Move the uploaded file to the uploads directory
        $targetFile = $uploadPath . $fileName;
        move_uploaded_file($_FILES['csvFile']['tmp_name'], $targetFile);

        // Open the CSV file for reading
        $file = fopen($targetFile, 'r');

        // Start a database transaction
        mysqli_begin_transaction($conn);

        try {
			$lastStudentID;
            // Read each row from the CSV file
            while (($rowData = fgetcsv($file)) !== false) {
				if ($rowData[0] === 'Student email') {
					continue;
				}
				
                // Extract data from the row
                $studentEmail = $rowData[0];
                $numSubjects = $rowData[2];
                $amountPaid = $rowData[3];
                $paymentType = $rowData[4];
                $paymentDate = $rowData[5];
                $totalAmount = $rowData[7];
				
				if (!empty($amountPaid)) {
					
					if (!empty($studentEmail)) {
						// Insert into students
						$stmt = $conn->prepare('INSERT INTO students (StudentEmail, NumSubjects, AmountPaid) VALUES (?, ?, ?)');
						$stmt->bind_param('sid', $studentEmail, $numSubjects, $totalAmount);
						$stmt->execute();
						$stmt->close();

						// Get the last inserted student ID
						$lastStudentID = $studentEmail;
					}

                // Insert into payments
                $stmt = $conn->prepare('INSERT INTO payments (StudentID, PaymentDate, PaymentAmount, PaymentType) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('ssds', $lastStudentID, $paymentDate, $amountPaid, $paymentType);
                $stmt->execute();
                $stmt->close();
					
				}

 
            }

            // Commit the transaction
            mysqli_commit($conn);

            echo 'Data imported successfully!';
        } catch (Exception $e) {
            // Roll back the transaction on error
            mysqli_rollback($conn);
            echo 'Error: ' . $e->getMessage();
        } finally {
            // Close the CSV file
            fclose($file);
        }
    } else {
        echo 'Error: No file uploaded.';
    }

    // Close the database connection
    mysqli_close($conn);
}
?>
