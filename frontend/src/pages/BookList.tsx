import React, { useEffect, useState } from 'react';
import { Button, Card, List, Modal, Form, Input, message } from 'antd';
import { BooksAPI } from '../api/api';
import { useNavigate } from 'react-router-dom';

export default function BookList() {
  const [books, setBooks] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);
  const [modalVisible, setModalVisible] = useState(false);
  const [form] = Form.useForm();
  const navigate = useNavigate();

  const loadBooks = async () => {
    setLoading(true);
    try {
      const data = await BooksAPI.list();
      setBooks(data);
    } catch (e: any) {
      if (e.message.includes('401')) {
        message.error('Session expired. Please log in again.');
        localStorage.removeItem('token');
        navigate('/auth');
      } else message.error(e.message);
    } finally {
      setLoading(false);
    }
  };

  const addBook = async (values: any) => {
    try {
      await BooksAPI.create(values);
      message.success('Book added');
      setModalVisible(false);
      form.resetFields();
      loadBooks();
    } catch (err: any) {
      message.error(err.message);
    }
  };

  const deleteBook = async (id: number) => {
    try {
      await BooksAPI.remove(id);
      message.success('Book deleted');
      loadBooks();
    } catch (err: any) {
      message.error(err.message);
    }
  };

  useEffect(() => {
    loadBooks();
  }, []);

  const logout = () => {
    localStorage.removeItem('token');
    navigate('/auth');
  };

  return (
    <div style={{ padding: 24, maxWidth: 800, margin: '0 auto' }}>
      <Card
        title="📚 Book List"
        extra={
          <>
            <Button onClick={() => setModalVisible(true)} type="primary">
              + Add
            </Button>{' '}
            <Button onClick={logout}>Logout</Button>
          </>
        }
      >
        <List
          bordered
          loading={loading}
          dataSource={books}
          renderItem={(book) => (
            <List.Item
              actions={[
                <Button danger onClick={() => deleteBook(book.id)}>
                  Delete
                </Button>,
              ]}
            >
              <List.Item.Meta
                title={book.title}
                description={`${book.author || 'Unknown'} — ${
                  book.description || 'No description'
                }`}
              />
            </List.Item>
          )}
        />
      </Card>

      <Modal
        open={modalVisible}
        title="Add New Book"
        onCancel={() => setModalVisible(false)}
        onOk={() => form.submit()}
      >
        <Form form={form} onFinish={addBook} layout="vertical">
          <Form.Item name="title" label="Title" rules={[{ required: true }]}>
            <Input />
          </Form.Item>
          <Form.Item name="author" label="Author">
            <Input />
          </Form.Item>
          <Form.Item name="description" label="Description">
            <Input.TextArea rows={3} />
          </Form.Item>
        </Form>
      </Modal>
    </div>
  );
}