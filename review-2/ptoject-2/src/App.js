import React, { useState } from "react";
import "./App.css";
import EventDetails from "./components/EventDetails";
import BookingForm from "./components/BookingForm";

const EVENT = {
  eventName: "TechFest 2026",
  departmentName: "Computer Science & Engineering",
  dateTime: "20 April 2026, 10:00 AM",
  venue: "Seminar Hall, Block A",
  ticketPrice: 200,
  totalTickets: 100,
};

function App() {
  const [availableTickets, setAvailableTickets] = useState(EVENT.totalTickets);
  const [bookingSummary, setBookingSummary] = useState(null);

  const handleBooking = (userData) => {
    setAvailableTickets((prev) => prev - userData.ticketCount);
    setBookingSummary({
      ...userData,
      eventName: EVENT.eventName,
      totalAmount: userData.ticketCount * EVENT.ticketPrice,
    });
  };

  return (
    <div className="container">
      <h1 className="page-title">Department Event Ticket Booking</h1>
      <p className="page-subtitle">Reserve your spot for the upcoming event</p>

      <EventDetails event={EVENT} availableTickets={availableTickets} />

      <BookingForm
        availableTickets={availableTickets}
        ticketPrice={EVENT.ticketPrice}
        onBook={handleBooking}
      />

      {bookingSummary && (
        <div className="summary-card">
          <h2>Booking Confirmation</h2>
          <div className="summary-grid">
            <div className="summary-item">
              <label>Name</label>
              <span>{bookingSummary.name}</span>
            </div>
            <div className="summary-item">
              <label>Email</label>
              <span>{bookingSummary.email}</span>
            </div>
            <div className="summary-item">
              <label>Department</label>
              <span>{bookingSummary.department}</span>
            </div>
            <div className="summary-item">
              <label>Event</label>
              <span>{bookingSummary.eventName}</span>
            </div>
            <div className="summary-item">
              <label>Tickets Booked</label>
              <span>{bookingSummary.ticketCount}</span>
            </div>
          </div>
          <div className="total-amount">
            Total: ₹{bookingSummary.totalAmount}
          </div>
        </div>
      )}
    </div>
  );
}

export default App;
