<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile Settings</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>

body {
    font-family: 'Poppins', sans-serif;
    background-color: #F3F5F7;
    margin: 0;
    padding: 40px;
}

.container {
    max-width: 1000px;
    margin: auto;
    background: #ffffff;
    padding: 35px;
    border-radius: 14px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.05);
}

h2 {
    margin-bottom: 5px;
    color: #1F2937;
}

.subtitle {
    color: #6B7280;
    font-size: 14px;
    margin-bottom: 25px;
}

.section {
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
    background: #FAFAFA;
}

.section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #1F2937;
}

.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 18px;
}

.form-group {
    flex: 1;
    display: flex;
    flex-direction: column;
}

label {
    font-size: 13px;
    margin-bottom: 6px;
    color: #374151;
}

input, select {
    padding: 11px;
    border: 1px solid #D1D5DB;
    border-radius: 8px;
    background-color: #ffffff;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
}

input:focus, select:focus {
    outline: none;
    border-color: #14532D;
    box-shadow: 0 0 0 2px rgba(20,83,45,0.1);
}

/* Green Button Theme */
button {
    padding: 12px 24px;
    background-color: #14532D;
    border: none;
    border-radius: 10px;
    color: white;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: 0.2s;
}

button:hover {
    background-color: #0F3D22;
}

h4 {
    margin-top: 20px;
    margin-bottom: 12px;
    font-size: 15px;
    color: #1F2937;
}

</style>
</head>
<body>

<div class="container">

    <h2>Profile Settings</h2>
    <div class="subtitle">Update your account's profile information.</div>

    <!-- Personal Information -->
    <div class="section">
        <h3>Personal Information</h3>

        <div class="form-row">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" value="Juan">
            </div>

            <div class="form-group">
                <label>Middle Name</label>
                <input type="text" value="D.">
            </div>

            <div class="form-group">
                <label>Last Name</label>
                <input type="text" value="Cruz">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" value="cruz.juan@plpasig.edu.ph">
            </div>

            <div class="form-group">
                <label>Age</label>
                <input type="number" value="25">
            </div>
        </div>
    </div>

    <!-- Academic Information -->
    <div class="section">
        <h3>Academic Information</h3>

        <div class="form-row">
            <div class="form-group">
                <label>Degree</label>
                <select>
                    <option>Select Degree</option>
                    <option selected>Information Technology</option>
                    <option>Computer Science</option>
                    <option>Accountancy</option>
                    <option>Business Administration Major in Marketing</option>
                    <option>Entrepreneurship</option>
                    <option>Hospitality Management</option>
                    <option>Nursing</option>
                    <option>Electronics & Communications Engineering</option>
                    <option>Secondary Education (English)</option>
                    <option>Secondary Education (Mathematics)</option>
                    <option>Secondary Education (Filipino)</option>
                    <option>Elementary Education</option>
                </select>
            </div>

            <div class="form-group">
                <label>Average Grade</label>
                <input type="text" value="90.00">
            </div>
        </div>

        <h4>Additional Academic Information</h4>

        <div class="form-row">
            <div class="form-group">
                <label>Average Professional Grade</label>
                <input type="text" value="88.00">
            </div>

            <div class="form-group">
                <label>Average Elective Grade</label>
                <input type="text" value="78.00">
            </div>

            <div class="form-group">
                <label>OJT Grade</label>
                <input type="text" value="87.00">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Soft Skills Average</label>
                <input type="text" value="80.00">
            </div>

            <div class="form-group">
                <label>Hard Skills Average</label>
                <input type="text" value="63.08">
            </div>
        </div>

    </div>

    <button>Save Changes</button>

</div>

</body>
</html>
