import React, { useEffect, useState } from "react";
import { Layout, Typography, Table, Button, message } from "antd";
import axios from "axios";

const { Header, Content } = Layout;
const { Title } = Typography;

const App: React.FC = () => {
  const [books, setBooks] = useState<any[]>([]);

  const fetchBooks = async () => {
    try {
      const res = await axios.get("/api/books", {
        headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
      });
      setBooks(res.data);
    } catch (e) {
      message.error("Failed to fetch books");
    }
  };

  useEffect(() => {
    fetchBooks();
  }, []);

  return (
    <Layout style={{ minHeight: "100vh" }}>
      <Header>
        <Title level={3} style={{ color: "white", margin: 0 }}>
          Adobe Code Challenge — Book Manager
        </Title>
      </Header>
      <Content style={{ padding: 24 }}>
        <Button type="primary" onClick={fetchBooks} style={{ marginBottom: 16 }}>
          Refresh
        </Button>
        <Table
          dataSource={books}
          rowKey="id"
          columns={[
            { title: "ID", dataIndex: "id" },
            { title: "Title", dataIndex: "title" },
            { title: "Author", dataIndex: "author" },
          ]}
        />
      </Content>
    </Layout>
  );
};

export default App;