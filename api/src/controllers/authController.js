const bcrypt = require("bcrypt");
const User = require("../models/User");

async function login(req, res) {
  const { username, password } = req.body;
  if (!username || !password) {
    return res.status(400).json({ error: "Missing credentials" });
  }

  const user = await User.findOne({ username });
  if (!user) return res.status(401).json({ error: "Invalid credentials" });

  const match = await bcrypt.compare(password, user.password);
  if (!match) return res.status(401).json({ error: "Invalid credentials" });

  req.session.userId = user._id.toString();
  req.session.username = user.username;
  req.session.role = user.role;

  res.json({
    ok: true,
    user: { id: req.session.userId, username: user.username, role: user.role },
  });
}

async function logout(req, res) {
  req.session.destroy((err) => {
    if (err) return res.status(500).json({ error: "Logout failed" });
    res.clearCookie("connect.sid");
    res.json({ ok: true });
  });
}

function me(req, res) {
  if (!req.session || !req.session.userId)
    return res.status(401).json({ error: "Not authenticated" });
  res.json({
    ok: true,
    user: {
      id: req.session.userId,
      username: req.session.username,
      role: req.session.role,
    },
  });
}

module.exports = { login, logout, me };
