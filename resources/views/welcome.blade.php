<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="container mt-4">

    <h2>Booking Requests</h2>
    <table class="table table-striped">
        <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Booked From</th>
              <th scope="col">Name</th>
              <th scope="col">Pickup</th>
              <th scope="col">Pickup Time</th>
              <th scope="col">Destination</th>
              <th scope="col">Passengers</th>
              <th scope="col">Note</th>
              <th scope="col">Booked at</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bookings as $booking)
            <tr>
              <th scope="row">{{ $loop->iteration }}</th>
              <td>{{ $booking->whatsappNumber->waid}}</td>
              <td>{{ $booking->lead_name }}</td>
              <td>{{ $booking->pickup }}</td>
              <td>{{ $booking->pickup_datetime }}</td>
              <td>{{ $booking->destination }}</td>
              <td>{{ $booking->passengers }}</td>
              <td>{{ $booking->note }}</td>
              <td>{{ $booking->created_at }}</td>
            </tr>
            @endforeach
          </tbody>
    </table>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
