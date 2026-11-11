<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title></title>
</head>

<body>
    <table>
        <tr>
            <td>Dear {{ $name }}!</td>
        </tr>
        <tr>
            <td>&nbsp;<br><br></td>
        </tr>
        <tr>
            <td>Your Vendor Account has been approved. Now you can login and add products.</td>
        </tr>
        <tr>
            <td>&nbsp;<br><br></td>
        </tr>
        <tr>
            <td>Your Vendor Account Details are as below :-<br></td>
        </tr>
        <tr>
            <td>&nbsp;<br></td>
        </tr>
        <tr>
            <td>Name: {{ $name }}</td>
        </tr>
        <tr>
            <td>&nbsp;<br></td>
        </tr>
        <tr>
            <td>Mobile: {{ $mobile }}</td>
        </tr>
        <tr>
            <td>&nbsp;<br></td>
        </tr>
        <tr>
            <td>Email: {{ $email }}</td>
        </tr>
        <tr>
            <td>&nbsp;<br></td>
        </tr>
        <tr>
            <td>Password: ***** (as chosen by you)</td>
        </tr>
        <tr>
            <td>&nbsp;<br><br></td>
        </tr>
        <tr>
            <td>Thanks & Regards,</td>
        </tr>
        <tr>
            <td>&nbsp;<br></td>
        </tr>
        <tr>
            <td>E-commerce Application</td>
        </tr>
    </table>
</body>

</html>
