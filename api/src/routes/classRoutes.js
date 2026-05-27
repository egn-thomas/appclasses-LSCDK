const express = require("express");
const {
  getHealth,
  getClasses,
  createClass,
} = require("../controllers/classController");

const router = express.Router();

router.get("/health", getHealth);
router.get("/classes", getClasses);
router.post("/classes", createClass);

module.exports = router;
