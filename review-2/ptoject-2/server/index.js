const express = require("express");
const cors = require("cors");
require("dotenv").config();
const db = require("./db");

const app = express();
app.use(cors());
app.use(express.json());

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

// ── GET all OPEN events (home page) ──────────────────────────────────────────
app.get("/api/events/open", async (_req, res) => {
  try {
    const [rows] = await db.query(
      "SELECT * FROM events WHERE status = 'open' ORDER BY id DESC"
    );
    res.json(rows);
  } catch (err) {
    res.status(500).json({ error: "Database error: " + err.message });
  }
});

// ── GET all events (admin, optional search) ───────────────────────────────────
app.get("/api/events", async (req, res) => {
  const search = req.query.search ? `%${req.query.search}%` : null;
  try {
    const [rows] = search
      ? await db.query(
          "SELECT * FROM events WHERE event_name LIKE ? OR department_name LIKE ? OR venue LIKE ? ORDER BY id DESC",
          [search, search, search]
        )
      : await db.query("SELECT * FROM events ORDER BY id DESC");
    res.json(rows);
  } catch (err) {
    res.status(500).json({ error: "Database error: " + err.message });
  }
});

// ── GET single event by ID ────────────────────────────────────────────────────
app.get("/api/events/:id", async (req, res) => {
  const id = parseInt(req.params.id);
  if (isNaN(id)) return res.status(400).json({ error: "Invalid event ID." });
  try {
    const [rows] = await db.query("SELECT * FROM events WHERE id = ?", [id]);
    if (rows.length === 0) return res.status(404).json({ error: "Event not found." });
    res.json(rows[0]);
  } catch (err) {
    res.status(500).json({ error: "Database error: " + err.message });
  }
});

// ── POST create event ─────────────────────────────────────────────────────────
app.post("/api/event", async (req, res) => {
  const { event_name, department_name, date_time, venue, ticket_price, available_tickets } = req.body;
  if (!event_name || !department_name || !date_time || !venue || !ticket_price || !available_tickets)
    return res.status(400).json({ error: "All fields are required." });

  const price   = parseFloat(ticket_price);
  const tickets = parseInt(available_tickets);
  if (isNaN(price)   || price   <= 0) return res.status(400).json({ error: "Invalid ticket price." });
  if (isNaN(tickets) || tickets <= 0) return res.status(400).json({ error: "Invalid ticket count." });

  try {
    const [result] = await db.query(
      "INSERT INTO events (event_name, department_name, date_time, venue, ticket_price, available_tickets, total_tickets, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'open')",
      [event_name.trim(), department_name.trim(), date_time.trim(), venue.trim(), price, tickets, tickets]
    );
    res.status(201).json({ message: "Event created successfully!", eventId: result.insertId });
  } catch (err) {
    res.status(500).json({ error: "Database error: " + err.message });
  }
});

// ── PUT update event ──────────────────────────────────────────────────────────
app.put("/api/events/:id", async (req, res) => {
  const id = parseInt(req.params.id);
  if (isNaN(id)) return res.status(400).json({ error: "Invalid event ID." });
  const { event_name, department_name, date_time, venue, ticket_price, available_tickets } = req.body;
  if (!event_name || !department_name || !date_time || !venue || !ticket_price || !available_tickets)
    return res.status(400).json({ error: "All fields are required." });

  const price   = parseFloat(ticket_price);
  const tickets = parseInt(available_tickets);
  if (isNaN(price)   || price   <= 0) return res.status(400).json({ error: "Invalid ticket price." });
  if (isNaN(tickets) || tickets <  0) return res.status(400).json({ error: "Invalid ticket count." });

  try {
    const [result] = await db.query(
      "UPDATE events SET event_name=?, department_name=?, date_time=?, venue=?, ticket_price=?, available_tickets=? WHERE id=?",
      [event_name.trim(), department_name.trim(), date_time.trim(), venue.trim(), price, tickets, id]
    );
    if (result.affectedRows === 0) return res.status(404).json({ error: "Event not found." });
    res.json({ message: "Event updated successfully!" });
  } catch (err) {
    res.status(500).json({ error: "Database error: " + err.message });
  }
});

// ── PATCH toggle event status (open <-> closed) ───────────────────────────────
app.patch("/api/events/:id/toggle-status", async (req, res) => {
  const id = parseInt(req.params.id);
  if (isNaN(id)) return res.status(400).json({ error: "Invalid event ID." });
  try {
    const [rows] = await db.query("SELECT status FROM events WHERE id = ?", [id]);
    if (rows.length === 0) return res.status(404).json({ error: "Event not found." });
    const newStatus = rows[0].status === "open" ? "closed" : "open";
    await db.query("UPDATE events SET status = ? WHERE id = ?", [newStatus, id]);
    res.json({ message: `Event ${newStatus === "open" ? "opened" : "closed"} successfully.`, status: newStatus });
  } catch (err) {
    res.status(500).json({ error: "Database error: " + err.message });
  }
});

// ── DELETE event ──────────────────────────────────────────────────────────────
app.delete("/api/events/:id", async (req, res) => {
  const id = parseInt(req.params.id);
  if (isNaN(id)) return res.status(400).json({ error: "Invalid event ID." });
  try {
    await db.query("DELETE FROM bookings WHERE event_id = ?", [id]);
    const [result] = await db.query("DELETE FROM events WHERE id = ?", [id]);
    if (result.affectedRows === 0) return res.status(404).json({ error: "Event not found." });
    res.json({ message: "Event deleted successfully." });
  } catch (err) {
    res.status(500).json({ error: "Database error: " + err.message });
  }
});

// ── POST book tickets ─────────────────────────────────────────────────────────
app.post("/api/book", async (req, res) => {
  const { name, email, department, ticketCount, eventId } = req.body;
  if (!name || !email || !department || !ticketCount || !eventId)
    return res.status(400).json({ error: "All fields are required." });
  if (!EMAIL_REGEX.test(email))
    return res.status(400).json({ error: "Invalid email format." });

  const count = parseInt(ticketCount);
  if (isNaN(count) || count <= 0)
    return res.status(400).json({ error: "Ticket count must be a positive number." });
  if (count > 10)
    return res.status(400).json({ error: "Maximum 10 tickets per booking." });

  try {
    const [eventRows] = await db.query(
      "SELECT id, available_tickets, ticket_price, status FROM events WHERE id = ?",
      [parseInt(eventId)]
    );
    if (eventRows.length === 0) return res.status(404).json({ error: "Event not found." });

    const { available_tickets, ticket_price, status } = eventRows[0];
    if (status === "closed")
      return res.status(400).json({ error: "This event is closed. Booking is not allowed." });
    if (count > available_tickets)
      return res.status(400).json({ error: `Only ${available_tickets} ticket(s) available.` });

    const [existing] = await db.query(
      "SELECT id FROM bookings WHERE email = ? AND event_id = ? AND status = 'confirmed'",
      [email.trim().toLowerCase(), parseInt(eventId)]
    );
    if (existing.length > 0)
      return res.status(400).json({ error: "You have already booked tickets for this event." });

    const totalAmount = count * ticket_price;
    const [result] = await db.query(
      "INSERT INTO bookings (event_id, name, email, department, ticket_count, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, 'confirmed')",
      [parseInt(eventId), name.trim(), email.trim().toLowerCase(), department.trim(), count, totalAmount]
    );
    await db.query(
      "UPDATE events SET available_tickets = available_tickets - ? WHERE id = ?",
      [count, parseInt(eventId)]
    );
    res.json({ message: "Booking successful!", totalAmount, bookingId: result.insertId });
  } catch (err) {
    res.status(500).json({ error: "Database error: " + err.message });
  }
});

// ── GET all bookings (admin) ──────────────────────────────────────────────────
app.get("/api/bookings", async (_req, res) => {
  try {
    const [rows] = await db.query(
      `SELECT b.id, b.name, b.email, b.department, b.ticket_count,
              b.total_amount, b.status, b.created_at, e.event_name
       FROM bookings b JOIN events e ON b.event_id = e.id
       ORDER BY b.created_at DESC`
    );
    res.json(rows);
  } catch (err) {
    res.status(500).json({ error: "Database error: " + err.message });
  }
});

// ── GET bookings by user email ────────────────────────────────────────────────
app.get("/api/bookings/user/:email", async (req, res) => {
  const email = req.params.email.toLowerCase();
  if (!EMAIL_REGEX.test(email)) return res.status(400).json({ error: "Invalid email." });
  try {
    const [rows] = await db.query(
      `SELECT b.id, b.name, b.email, b.department, b.ticket_count,
              b.total_amount, b.status, b.created_at,
              e.event_name, e.date_time, e.venue, e.status AS event_status
       FROM bookings b JOIN events e ON b.event_id = e.id
       WHERE b.email = ? ORDER BY b.created_at DESC`,
      [email]
    );
    res.json(rows);
  } catch (err) {
    res.status(500).json({ error: "Database error: " + err.message });
  }
});

// ── DELETE cancel booking ─────────────────────────────────────────────────────
app.delete("/api/bookings/:id", async (req, res) => {
  const id = parseInt(req.params.id);
  if (isNaN(id)) return res.status(400).json({ error: "Invalid booking ID." });
  try {
    const [rows] = await db.query(
      "SELECT * FROM bookings WHERE id = ? AND status = 'confirmed'", [id]
    );
    if (rows.length === 0)
      return res.status(404).json({ error: "Booking not found or already cancelled." });
    await db.query("UPDATE bookings SET status = 'cancelled' WHERE id = ?", [id]);
    await db.query(
      "UPDATE events SET available_tickets = available_tickets + ? WHERE id = ?",
      [rows[0].ticket_count, rows[0].event_id]
    );
    res.json({ message: "Booking cancelled. Tickets restored." });
  } catch (err) {
    res.status(500).json({ error: "Database error: " + err.message });
  }
});

// ── GET dashboard stats ───────────────────────────────────────────────────────
app.get("/api/stats", async (_req, res) => {
  try {
    const [[{ totalEvents }]]    = await db.query("SELECT COUNT(*) AS totalEvents FROM events");
    const [[{ openEvents }]]     = await db.query("SELECT COUNT(*) AS openEvents FROM events WHERE status='open'");
    const [[{ closedEvents }]]   = await db.query("SELECT COUNT(*) AS closedEvents FROM events WHERE status='closed'");
    const [[{ totalBookings }]]  = await db.query("SELECT COUNT(*) AS totalBookings FROM bookings WHERE status='confirmed'");
    const [[{ totalRevenue }]]   = await db.query("SELECT COALESCE(SUM(total_amount),0) AS totalRevenue FROM bookings WHERE status='confirmed'");
    const [[{ totalTickets }]]   = await db.query("SELECT COALESCE(SUM(ticket_count),0) AS totalTickets FROM bookings WHERE status='confirmed'");
    const [[{ cancelledCount }]] = await db.query("SELECT COUNT(*) AS cancelledCount FROM bookings WHERE status='cancelled'");
    res.json({ totalEvents, openEvents, closedEvents, totalBookings, totalRevenue, totalTickets, cancelledCount });
  } catch (err) {
    res.status(500).json({ error: "Database error: " + err.message });
  }
});

const PORT = process.env.PORT || 5000;
app.listen(PORT, () => console.log(`Server running on port ${PORT}`));
