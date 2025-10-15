import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { App as AntApp } from 'antd';
import AuthForm from './pages/AuthForm';
import BookList from './pages/BookList';
import ProtectedRoute from './components/ProtectedRoute';
import 'antd/dist/reset.css';

export default function App() {
  return (
    <AntApp> 
      <BrowserRouter>
        <Routes>
          <Route path="/auth" element={<AuthForm />} />
          <Route
            path="/books"
            element={
              <ProtectedRoute>
                <BookList />
              </ProtectedRoute>
            }
          />
          <Route path="*" element={<Navigate to="/auth" />} />
        </Routes>
      </BrowserRouter>
	</AntApp> 
  );
}