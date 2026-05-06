import React from "react";

function EventDetails({ event, availableTickets }) {
  const badgeClass =
    availableTickets === 0
      ? "ticket-badge sold-out"
      : availableTickets <= 10
      ? "ticket-badge low"
      : "ticket-badge";

  const badgeLabel =
    availableTickets === 0 ? "Sold Out" : `${availableTickets} left`;

  return (
    <div className="card">
      <h2>Event Details</h2>
      <div className="detail-grid">
        <div className="detail-item">
          <label>Event Name</label>
          <span>{event.eventName}</span>
        </div>
        <div className="detail-item">
          <label>Department</label>
          <span>{event.departmentName}</span>
        </div>
        <div className="detail-item">
          <label>Date &amp; Time</label>
          <span>{event.dateTime}</span>
        </div>
        <div className="detail-item">
          <label>Venue</label>
          <span>{event.venue}</span>
        </div>
        <div className="detail-item">
          <label>Ticket Price</label>
          <span>₹{event.ticketPrice}</span>
        </div>
        <div className="detail-item">
          <label>Available Tickets</label>
          <span className={badgeClass}>{badgeLabel}</span>
        </div>
      </div>
    </div>
  );
}

export default EventDetails;
