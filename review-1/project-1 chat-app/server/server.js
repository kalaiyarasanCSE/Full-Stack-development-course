const express = require("express");
const http = require("http");
const { Server } = require("socket.io");

const app = express();
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: "*" } });

let users = {};

io.on("connection", (socket) => {

    socket.on("join", (username) => {
        users[username] = socket.id;
        io.emit("onlineUsers", Object.keys(users));
    });

    socket.on("sendMessage", (data) => {
        if (users[data.receiver]) {
            io.to(users[data.receiver]).emit("receiveMessage", data);
        }
    });

    socket.on("typing", (user) => {
        socket.broadcast.emit("typing", user);
    });

    socket.on("disconnect", () => {
        for (let u in users) {
            if (users[u] === socket.id) delete users[u];
        }
        io.emit("onlineUsers", Object.keys(users));
    });

});
server.listen(4000, () => {
    console.log("Server running on port 4000");
});