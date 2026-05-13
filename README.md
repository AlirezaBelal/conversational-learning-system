# Conversational Learning System

## Overview

This project implements a Telegram-based learning system with persistent user state and structured interaction flows.

It manages:

* Conversational flows
* User state tracking
* Course and quiz delivery
* Support and feedback handling

---

## System Design

### 1. Bot Interface

* Telegram bot as primary user interface
* Command-based interaction system

### 2. State Management

* Stores user state in database
* Supports multi-step interactions

### 3. Content Delivery

* Courses module
* Quiz module
* Support module

---

## Core Features

* Structured learning flows
* Quiz-based assessment system
* User support channel
* Persistent session handling

---

## Database Layer

* MySQL based storage
* User state tracking
* Course and quiz data models

---

## Architecture

Telegram API → Bot Logic → State Manager → Database → Response Engine

---

## Requirements

* PHP 7.4+
* Composer
* MySQL
* Telegram Bot API

---

## Setup

1. Install dependencies
   composer install

2. Configure environment
   Copy .env.example to .env
   Set database credentials and bot token

3. Deploy to server
   Upload to web server
   Configure webhook endpoint

---

## API Commands

* /help → system guide
* /courses → available courses
* /quiz → start assessment flow
* /contact → support channel

---
