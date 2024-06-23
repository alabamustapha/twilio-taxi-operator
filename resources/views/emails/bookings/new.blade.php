<h2>New booking requested from {{ $booking->lead_name }} </h2>
<div>
    <h3>Booking details</h3>
    <table>
        <tbody>
            <tr>
                <td>Phone</td>
                <td>{{ $booking->whatsappNumber->waid }}</td>
            </tr>
            <tr>
                <td>Pickup</td>
                <td>{{ $booking->pickup }}</td>
            </tr>
            <tr>
                <td>Pickup Datetime</td>
                <td>{{ $booking->pickup_datetime }}</td>
            </tr>
            <tr>
                <td>Destination</td>
                <td>{{ $booking->destination }}</td>
            </tr>
            <tr>
                <td>Passengers</td>
                <td>{{ $booking->passengers }}</td>
            </tr>
            <tr>
                <td>Note</td>
                <td>{{ $booking->note }}</td>
            </tr>
        </tbody>
    </table>
</div>
