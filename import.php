<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle file upload
    if (isset($_FILES['html_file']) && $_FILES['html_file']['error'] === UPLOAD_ERR_OK) {
        $uploadedFile = $_FILES['html_file']['tmp_name'];
        // Use the uploaded file instead of the hardcoded file
        $html = file_get_contents($uploadedFile);
        
        // Use DOMDocument to parse the HTML
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Find the table with class 'tabela'
        $table = $dom->getElementsByTagName('table');
        foreach ($table as $tbl) {
            if ($tbl->getAttribute('class') === 'tabela') {
                $scheduleTable = $tbl;
                break;
            }
        }

        // Extract 'klasa' value
        $klasaElement = $dom->getElementsByTagName('span');
        foreach ($klasaElement as $span) {
            if ($span->getAttribute('class') === 'tytulnapis') {
                $klasa = trim($span->nodeValue);
                break;
            }
        }

        // MySQL connection parameters
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "plan_lekcji";

        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Create table if not exists
        $sql_create = "CREATE TABLE IF NOT EXISTS plan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nr INT,
            godz VARCHAR(20),
            poniedzialek TEXT,
            wtorek TEXT,
            sroda TEXT,
            czwartek TEXT,
            piatek TEXT,
            klasa VARCHAR(10)
        );";

        if ($conn->query($sql_create) === FALSE) {
            echo "Error creating table: " . $conn->error;
        }

        // Iterate through table rows
        $rows = $scheduleTable->getElementsByTagName('tr');
        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // Skip header row
            $cells = $row->getElementsByTagName('td');
            if ($cells->length < 7) continue; // Ensure enough cells

            $nr = intval(trim($cells->item(0)->nodeValue));
            $godz = trim($cells->item(1)->nodeValue);
            $pon = trim($cells->item(2)->nodeValue);
            $wt = trim($cells->item(3)->nodeValue);
            $sro = trim($cells->item(4)->nodeValue);
            $czw = trim($cells->item(5)->nodeValue);
            $piat = trim($cells->item(6)->nodeValue);

            // Prepare SQL insert statement
            $sql = "INSERT INTO plan (nr, godz, poniedzialek, wtorek, sroda, czwartek, piatek, klasa) VALUES (
                $nr, '$godz', '" . addslashes($pon) . "', '" . addslashes($wt) . "', '" . addslashes($sro) . "', '" . addslashes($czw) . "', '" . addslashes($piat) . "', '$klasa');";

            if ($conn->query($sql) === FALSE) {
                echo "Error inserting record: " . $conn->error;
            }
        }

        // Close MySQL connection
        $conn->close();

        echo "plan.sql has been generated successfully.";
    } else {
        echo "Error uploading file.";
    }
} else {
    // Display upload form
    ?>
    <form action="import.php" method="post" enctype="multipart/form-data">
        <label for="html_file">Wybierz plik HTML do importu:</label>
        <input type="file" name="html_file" id="html_file" accept=".html" required>
        <input type="submit" value="Importuj">
    </form>
    <?php
}
?>
