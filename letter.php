<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Appointment as Examination Supervisor</title>
<style>
    
</style>
</head>
<body>

<div class="page">
    <div class="dom-alert"><sup>*</sup>Changes made in the given document will be applicable everywhere.</div>
    <div class="header">
        <h2 class="editable" contenteditable="true" data-key="college_name">GOVERNMENT POLYTECHNIC, NASHIK</h2>
        <p class="editable" contenteditable="true" data-key="section_name">Examination Section</p>
    </div>

    <div class="date-ref">
        <span><div class="date">Date : </div>________</span>
        <span><div class="ref-no">Ref : </div>________</span>
    </div>

    <div class="content">
        
        <div class="online">
            To,<br>
            faculty_name Here,<br>
            <p class="editable" contenteditable="true" data-key="department">
                Department of
            </p>
            <p class="editable" contenteditable="true" data-key="college_address">
                Government Polytechnic, Nashik
            </p>
        </div> 

        <div class="inline">
            Subject :
            <p class="subject editable" contenteditable="true" data-key="subject_name">
                Appointment as Examination Supervisor
            </p>
        </div>

        <p class="editable" contenteditable="true" data-key="body_para_1">
            Sir / Madam,
        </p>

        <p class="editable" contenteditable="true" data-key="body_para_2">
            You are hereby appointed as an Examination Supervisor for the forthcoming examinations as per the schedule mentioned below. You are requested to report at the examination center at least 30 minutes before the commencement of the examination and perform the assigned duties sincerely.
        </p>

        <p class="editable" contenteditable="true" data-key="body_para_3">
            Your presence and cooperation are mandatory as per institutional and government examination norms.
        </p>

        <span class="inline">
            <strong>Show Table :</strong>
            <span class="inline" data-key="show_table">
                <label>
                    <input type="radio" class="tble-view" name="table-view" value="yes"> YES
                </label>
                <label>
                    <input type="radio" class="tble-view" name="table-view" value="no"> NO
                </label>
            </span>
        </span>
        <table contenteditable="false" id="scheduleTable">
            <thead>
                <tr>
                    <th>Sr.No</th>
                    <th>Date</th>
                    <th>Slot</th>
                </tr>
            </thead>
            <tbody contenteditable="false">
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <div class="signature">
            <p class="editable" contenteditable="true" data-key="closing_text">Yours faithfully,</p>
            <span class="sign-area">
                <img  src="./upload/signature.jpg" id="signatureImg" data-key="signature" onclick="uploadImage(this)" style="cursor:pointer;">
                <input  type="file"  accept="image/*" id="signatureInput" hidden>
                <strong class="editable" contenteditable="true" data-key="official_designation">Chief Incharge</strong>
                <span class="editable" contenteditable="true" data-key="off_name">Principal</span>
                <span class="editable" contenteditable="true" data-key="off_address">Government Polytechnic, Nashik</span>
            </span>
        </div>
    </div>
    <button onclick="saveLetter()">Save Document</button>
</div>
<script>
    function uploadImage(imgEl) {
        const input = document.getElementById('signatureInput');

        input.onchange = () => {
            const file = input.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = e => {
                imgEl.src = e.target.result; // base64 preview
                imgEl.dataset.value = e.target.result; // store for JSON
            };
            reader.readAsDataURL(file);
        };

        input.click();
    }

    function saveLetter() {

        const formData = new FormData();
        let jsonData = {};

        // Loop through all data-key elements
        document.querySelectorAll('[data-key]').forEach(el => {

            // RADIO
            if (el.querySelector('input[type=radio]')) {
                const checked = el.querySelector('input[type=radio]:checked');
                formData.append(el.dataset.key, checked ? checked.value : 'yes');
            }

            // IMAGE
            else if (el.tagName === 'IMG') {
                if (el.dataset.key) {
                    let file = document.getElementById('signatureInput');
                    if (file){
                        formData.append(el.dataset.key, file.files[0] || '');
                    }
                }
            }

            // TEXT
            else {
                formData.append(el.dataset.key, el.innerText.trim());
            }
        });

        formData.forEach((value, key) => {
            console.log(`${key}: ${value}`);
        });

        fetch('./Backend/save_letter.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => alert(res.message));
    }

    const table = document.getElementById('scheduleTable');

    document.querySelectorAll('.tble-view').forEach(radio => {
        radio.addEventListener('change', function () {
            if (this.value === 'no') {
                table.style.display = 'none';
            } else {
                table.style.display = 'table';
            }
        });
    });
</script>
</body>
</html>
