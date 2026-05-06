import React, { useState } from "react";

const INITIAL_FORM = { name: "", email: "", department: "", ticketCount: "" };

function validate(formData, availableTickets) {
  const errors = {};
  if (!formData.name.trim()) errors.name = "Name is required.";
  if (!formData.email.trim()) {
    errors.email = "Email is required.";
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
    errors.email = "Enter a valid email address.";
  }
  if (!formData.department.trim()) errors.department = "Department is required.";
  const tickets = parseInt(formData.ticketCount);
  if (!formData.ticketCount) {
    errors.ticketCount = "Number of tickets is required.";
  } else if (isNaN(tickets) || tickets <= 0) {
    errors.ticketCount = "Enter a positive number of tickets.";
  } else if (tickets > availableTickets) {
    errors.ticketCount = `Only ${availableTickets} ticket(s) available.`;
  }
  return errors;
}

function BookingForm({ availableTickets, ticketPrice, onBook }) {
  const [formData, setFormData] = useState(INITIAL_FORM);
  const [errors, setErrors] = useState({});
  const [success, setSuccess] = useState("");

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    // Clear field error on change
    if (errors[name]) setErrors((prev) => ({ ...prev, [name]: "" }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    setSuccess("");
    const validationErrors = validate(formData, availableTickets);
    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      return;
    }
    setErrors({});
    onBook({ ...formData, ticketCount: parseInt(formData.ticketCount) });
    setSuccess("Booking confirmed! Check the summary below.");
    setFormData(INITIAL_FORM);
  };

  const handleReset = () => {
    setFormData(INITIAL_FORM);
    setErrors({});
    setSuccess("");
  };

  const isSoldOut = availableTickets === 0;

  return (
    <div className="card">
      <h2>Book Tickets</h2>

      {isSoldOut && (
        <div className="alert alert-error">
          Sorry, all tickets are sold out.
        </div>
      )}

      {success && <div className="alert alert-success">{success}</div>}

      <form onSubmit={handleSubmit} noValidate>
        <div className="form-group">
          <label htmlFor="name">Full Name</label>
          <input
            id="name"
            type="text"
            name="name"
            placeholder="e.g. Rahul Sharma"
            value={formData.name}
            onChange={handleChange}
            className={errors.name ? "input-error" : ""}
          />
          {errors.name && <p className="field-error">{errors.name}</p>}
        </div>

        <div className="form-group">
          <label htmlFor="email">Email ID</label>
          <input
            id="email"
            type="email"
            name="email"
            placeholder="e.g. rahul@college.edu"
            value={formData.email}
            onChange={handleChange}
            className={errors.email ? "input-error" : ""}
          />
          {errors.email && <p className="field-error">{errors.email}</p>}
        </div>

        <div className="form-group">
          <label htmlFor="department">Department</label>
          <input
            id="department"
            type="text"
            name="department"
            placeholder="e.g. Computer Science"
            value={formData.department}
            onChange={handleChange}
            className={errors.department ? "input-error" : ""}
          />
          {errors.department && <p className="field-error">{errors.department}</p>}
        </div>

        <div className="form-group">
          <label htmlFor="ticketCount">Number of Tickets</label>
          <input
            id="ticketCount"
            type="number"
            name="ticketCount"
            placeholder="e.g. 2"
            min="1"
            max={availableTickets}
            value={formData.ticketCount}
            onChange={handleChange}
            className={errors.ticketCount ? "input-error" : ""}
          />
          {errors.ticketCount && <p className="field-error">{errors.ticketCount}</p>}
        </div>

        <div className="btn-row">
          <button
            type="submit"
            className="btn btn-primary"
            disabled={isSoldOut}
          >
            Book Now
          </button>
          <button
            type="button"
            className="btn btn-secondary"
            onClick={handleReset}
          >
            Reset
          </button>
        </div>
      </form>
    </div>
  );
}

export default BookingForm;
