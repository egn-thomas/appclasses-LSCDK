const mongoose = require("mongoose");

const studentSchema = new mongoose.Schema({
  className: {
    type: String,
    required: true,
  },
  firstName: {
    type: String,
    required: true,
  },
  lastName: {
    type: String,
    required: true,
  },
  gender: {
    type: String,
    enum: ["M", "F", "Autre"],
    required: true,
  },
  age: {
    type: Number,
    required: true,
  },
  dateOfBirth: {
    type: Date,
    required: true,
  },
  formation: {
    type: String,
    required: true,
  },
  options: {
    type: [String], // Array of option IDs
    default: [],
  },
  createdAt: {
    type: Date,
    default: Date.now,
  },
});

module.exports = mongoose.model("Student", studentSchema);
