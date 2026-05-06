// CONNECT SOCKET (change port if needed)
const socket = io("http://localhost:4000");

// GET USER
const user = localStorage.getItem("user");

// PROTECT PAGE
if (!user) {
    alert("Please login first");
    window.location = "index.html";
}

// DOM ELEMENTS
const chatBox = document.getElementById("chatBox");
const messageInput = document.getElementById("message");
const typingBox = document.getElementById("typing");
const userList = document.getElementById("userList");

let currentChatUser = "";
let pickerVisible = false;

// DEBUG
console.log("Logged in user:", user);

// JOIN SOCKET
socket.emit("join", user);

// ================= ONLINE USERS =================
socket.on("onlineUsers", (users) => {
    console.log("Online users:", users);

    userList.innerHTML = "";

    users.forEach(u => {
        if (u !== user) {
            let li = document.createElement("li");
            li.className = "list-group-item";
            li.innerText = u;

            li.onclick = () => {
                currentChatUser = u;
                document.getElementById("chatUser").innerText = u;

                chatBox.innerHTML = "";
                loadMessages();
            };

            userList.appendChild(li);
        }
    });
});

// ================= SEND MESSAGE =================
function sendMessage() {
    let msg = messageInput.value.trim();

    if (!msg || !currentChatUser) {
        alert("Select user and type message");
        return;
    }

    // SEND REAL-TIME
    socket.emit("sendMessage", {
        sender: user,
        receiver: currentChatUser,
        message: msg
    });

    // SHOW OWN MESSAGE
    displayMessage(user, msg, "sent");

    // SAVE TO DATABASE
    fetch("../php/saveMessage.php", {
        method: "POST",
        body: new URLSearchParams({
            sender: user,
            receiver: currentChatUser,
            message: msg
        })
    });

    messageInput.value = "";
}

// ================= RECEIVE MESSAGE =================
socket.on("receiveMessage", (data) => {
    console.log("Received:", data);

    if (data.sender === currentChatUser) {
        displayMessage(data.sender, data.message, "received");
    }
});

// ================= DISPLAY MESSAGE =================
function displayMessage(sender, msg, type) {
    let div = document.createElement("div");
    div.className = "message " + type;
    div.innerHTML = `<b>${sender}:</b> ${msg}`;

    chatBox.appendChild(div);
    chatBox.scrollTop = chatBox.scrollHeight;
}

// ================= LOAD OLD MESSAGES =================
function loadMessages() {
    fetch(`../php/getMessages.php?sender=${user}&receiver=${currentChatUser}`)
    .then(res => res.text())
    .then(data => {
        chatBox.innerHTML = data;
        chatBox.scrollTop = chatBox.scrollHeight;
    });
}

// ================= TYPING =================
messageInput.addEventListener("keypress", () => {
    socket.emit("typing", user);
});

socket.on("typing", (u) => {
    if (u === currentChatUser) {
        typingBox.innerText = u + " is typing...";
        setTimeout(() => {
            typingBox.innerText = "";
        }, 1000);
    }
});

// ================= EMOJI =================
function toggleEmoji() {
    const picker = document.getElementById("emojiPicker");

    if (!pickerVisible) {
        picker.innerHTML = "";

        const emojiPicker = new EmojiMart.Picker({
            onEmojiSelect: (emoji) => {
                messageInput.value += emoji.native;
            }
        });

        picker.appendChild(emojiPicker);
        pickerVisible = true;
    } else {
        picker.innerHTML = "";
        pickerVisible = false;
    }
}