#!/bin/bash

echo "Starting Socket.IO Server for Event Management System..."
echo ""
echo "Make sure you have Node.js installed!"
echo ""
echo "Server will start on: http://localhost:3000"
echo "Test interface: http://localhost:3000"
echo ""
echo "Press Ctrl+C to stop the server"
echo ""

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "Error: Node.js is not installed!"
    echo "Please install Node.js from https://nodejs.org/"
    exit 1
fi

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo "Error: npm is not installed!"
    echo "Please install npm"
    exit 1
fi

# Install dependencies if node_modules doesn't exist
if [ ! -d "node_modules" ]; then
    echo "Installing dependencies..."
    npm install
fi

# Start the server
npm start
