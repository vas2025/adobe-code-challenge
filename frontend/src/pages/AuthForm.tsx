import React, { useState, useEffect } from 'react';
import { Tabs, Form, Input, Button, Card } from 'antd';
import { useNavigate } from 'react-router-dom';
import { AuthAPI } from '../api/api';

export default function AuthForm() {
  const [loading, setLoading] = useState(false);
  const [activeTab, setActiveTab] = useState<'login' | 'register'>('login');
  const [notice, setNotice] = useState<string | null>(null);
  const [noticeType, setNoticeType] = useState<'error' | 'success' | null>(null);
  const navigate = useNavigate();

  useEffect(() => {
    if (notice) {
      const t = setTimeout(() => setNotice(null), 5000);
      return () => clearTimeout(t);
    }
  }, [notice]);

  const handleSubmit = async (type: 'login' | 'register', values: any) => {
    
    setNotice('Submitting...');
    setNoticeType(null);
    setLoading(true);
    try {
      if (type === 'register') {
        await AuthAPI.register(values);
        setNotice('Registration successful! Please log in.');
        setNoticeType('success');
        setActiveTab('login');
        return;
      }

      const data = await AuthAPI.login(values);
      localStorage.setItem('token', data.token);
      setNotice('Login successful!');
      setNoticeType('success');
      navigate('/books');
    } catch (err: any) {
      console.error('API error:', err);

      let msg = 'Something went wrong. Please try again.';

      if (err?.message) {
		  
		const match = err.message.match(/\{[\s\S]*\}/);
		
        if (match) {
          try {
            const parsed = JSON.parse(match[0]);
            msg = parsed.error || parsed.message || msg;
          } catch {
            msg = err.message;
          }
        } else {
          msg = err.message;
        }
      }

      setNotice(msg);
      setNoticeType('error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div
      style={{
        height: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: '#f5f5f5',
      }}
    >
      <Card style={{ width: 400, boxShadow: '0 4px 12px rgba(0,0,0,0.1)' }}>
        {/* UI card */}
        {notice && (
          <div
            style={{
              marginBottom: 12,
              padding: '8px 12px',
              borderRadius: 4,
              background: noticeType === 'error' ? '#ff4d4f' : '#52c41a',
              color: 'white',
              fontWeight: 600,
              textAlign: 'center',
            }}
          >
            {notice}
          </div>
        )}
        <Tabs
          centered
          activeKey={activeTab}
          onChange={(key) => setActiveTab(key as 'login' | 'register')}
        >
          <Tabs.TabPane tab="Login" key="login">
            <Form onFinish={(v) => handleSubmit('login', v)} layout="vertical">
              <Form.Item name="email" label="Email" rules={[{ required: true }]}> 
                <Input type="email" />
              </Form.Item>
              <Form.Item
                name="password"
                label="Password"
                rules={[{ required: true }]}
              >
                <Input.Password />
              </Form.Item>
              <Button type="primary" htmlType="submit" block loading={loading}>
                Login
              </Button>
            </Form>
          </Tabs.TabPane>

          <Tabs.TabPane tab="Register" key="register">
            <Form
              onFinish={(v) => handleSubmit('register', v)}
              layout="vertical"
            >
              <Form.Item name="email" label="Email" rules={[{ required: true }]}>
                <Input type="email" />
              </Form.Item>
              <Form.Item
                name="password"
                label="Password"
                rules={[{ required: true }]}
              >
                <Input.Password />
              </Form.Item>
              <Button type="primary" htmlType="submit" block loading={loading}>
                Register
              </Button>
            </Form>
          </Tabs.TabPane>
        </Tabs>
      </Card>
    </div>
  );
}